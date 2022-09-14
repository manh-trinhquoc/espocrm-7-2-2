<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Authentication\TwoFactor\Sms;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Utils\Config;
use Espo\Core\Sms\SmsSender;
use Espo\Core\Sms\SmsFactory;
use Espo\Core\Utils\Language;
use Espo\Core\Field\DateTime;

use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Condition as Cond;

use Espo\Entities\User;
use Espo\Entities\Sms;
use Espo\Entities\TwoFactorCode;
use Espo\Entities\UserData;

use Espo\Repositories\UserData as UserDataRepository;

use const STR_PAD_LEFT;

class Util
{
    /**
     * A lifetime of a code.
     */
    private const CODE_LIFETIME_PERIOD = '10 minutes';

    /*
     * A max number of attempts to try a single code.
     */
    private const CODE_ATTEMPTS_COUNT = 5;

    /**
     * A length of a code.
     */
    private const CODE_LENGTH = 6;

    /**
     * A max number of codes tried by a user in a period defined by `CODE_LIMIT_PERIOD`.
     */
    private const CODE_LIMIT = 5;

    /**
     * A period for limiting trying to too many codes.
     */
    private const CODE_LIMIT_PERIOD = '20 minutes';

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $config;

    private $smsSender;

    private $language;

    private $smsFactory;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        SmsSender $smsSender,
        Language $language,
        SmsFactory $smsFactory
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->smsSender = $smsSender;
        $this->language = $language;
        $this->smsFactory = $smsFactory;
    }

    public function storePhoneNumber(User $user, string $phoneNumber): void
    {
        $this->checkPhoneNumberIsUsers($user, $phoneNumber);

        $userData = $this->getUserDataRepository()->getByUserId($user->getId());

        if (!$userData) {
            throw new Error();
        }

        $userData->set('auth2FASmsPhoneNumber', $phoneNumber);

        $this->entityManager->saveEntity($userData);
    }

    public function verifyCode(User $user, string $code): bool
    {
        $codeEntity = $this->findCodeEntity($user);

        if (!$codeEntity) {
            return false;
        }

        if ($codeEntity->getAttemptsLeft() <= 1) {
            $this->decrementAttemptsLeft($codeEntity);
            $this->inactivateExistingCodeRecords($user);

            return false;
        }

        if ($codeEntity->getCode() !== $code) {
            $this->decrementAttemptsLeft($codeEntity);

            return false;
        }

        if (!$this->isCodeValidByLifetime($codeEntity)) {
            $this->inactivateExistingCodeRecords($user);

            return false;
        }

        $this->inactivateExistingCodeRecords($user);

        return true;
    }

    public function sendCode(User $user, ?string $phoneNumber = null): void
    {
        if ($phoneNumber === null) {
            $phoneNumber = $this->getPhoneNumber($user);
        }

        $this->checkPhoneNumberIsUsers($user, $phoneNumber);
        $this->checkCodeLimit($user);

        $code = $this->generateCode();

        $this->inactivateExistingCodeRecords($user);
        $this->createCodeRecord($user, $code);

        $sms = $this->createSms($user, $code, $phoneNumber);

        $this->smsSender->send($sms);
    }

    private function isCodeValidByLifetime(TwoFactorCode $codeEntity): bool
    {
        $period = $this->config->get('auth2FASmsCodeLifetimePeriod') ?? self::CODE_LIFETIME_PERIOD;

        $validUntil = $codeEntity->getCreatedAt()->modify($period);

        if (DateTime::createNow()->diff($validUntil)->invert) {
            return false;
        }

        return true;
    }

    private function findCodeEntity(User $user): ?TwoFactorCode
    {
        /** @var ?TwoFactorCode */
        return $this->entityManager
            ->getRDBRepository(TwoFactorCode::ENTITY_TYPE)
            ->where([
                'method' => 'Sms',
                'userId' => $user->getId(),
                'isActive' => true,
            ])
            ->findOne();
    }

    private function getPhoneNumber(User $user): string
    {
        $userData = $this->getUserDataRepository()->getByUserId($user->getId());

        if (!$userData) {
            throw new Error("UserData not found.");
        }

        $phoneNumber = $userData->get('auth2FASmsPhoneNumber');

        if ($phoneNumber) {
            return $phoneNumber;
        }

        if ($user->getPhoneNumberGroup()->getCount() === 0) {
            throw new Error("User does not have phone number.");
        }

        /** @var string */
        return $user->getPhoneNumberGroup()->getPrimaryNumber();
    }

    private function checkPhoneNumberIsUsers(User $user, string $phoneNumber): void
    {
        $userNumberList = array_map(
            function (string $item) {
                return strtolower($item);
            },
            $user->getPhoneNumberGroup()->getNumberList()
        );

        if (!in_array(strtolower($phoneNumber), $userNumberList)) {
            throw new Forbidden("Phone number is not one of user's.");
        }
    }

    private function checkCodeLimit(User $user): void
    {
        $limit = $this->config->get('auth2FASmsCodeLimit') ?? self::CODE_LIMIT;
        $period = $this->config->get('auth2FASmsCodeLimitPeriod') ?? self::CODE_LIMIT_PERIOD;

        $from = DateTime::createNow()
            ->modify('-' . $period)
            ->getString();

        $count = $this->entityManager
            ->getRDBRepository(TwoFactorCode::ENTITY_TYPE)
            ->where(
                Cond::and(
                    Cond::equal(Cond::column('method'), 'Sms'),
                    Cond::equal(Cond::column('userId'), $user->getId()),
                    Cond::greaterOrEqual(Cond::column('createdAt'), $from),
                    Cond::lessOrEqual(Cond::column('attemptsLeft'), 0),
                )
            )
            ->count();

        if ($count >= $limit) {
            throw new Forbidden("Max code count exceeded.");
        }
    }

    private function generateCode(): string
    {
        $codeLength = $this->config->get('auth2FASmsCodeLength') ?? self::CODE_LENGTH;

        $max = pow(10, $codeLength) - 1;

        return str_pad(
            (string) random_int(0, $max),
            $codeLength,
            '0',
            STR_PAD_LEFT
        );
    }

    private function createSms(User $user, string $code, string $phoneNumber): Sms
    {
        $fromNumber = $this->config->get('outboundSmsFromNumber');

        $bodyTpl = $this->language->translateLabel('yourAuthenticationCode', 'messages', 'User');

        $body = str_replace('{code}', $code, $bodyTpl);

        $sms = $this->smsFactory->create();

        $sms->setFromNumber($fromNumber);
        $sms->setBody($body);
        $sms->addToNumber($phoneNumber);

        return $sms;
    }

    private function inactivateExistingCodeRecords(User $user): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(TwoFactorCode::ENTITY_TYPE)
            ->where([
                'userId' => $user->getId(),
                'method' => 'Sms',
            ])
            ->set([
                'isActive' => false,
            ])
            ->build();

        $this->entityManager
            ->getQueryExecutor()
            ->execute($query);
    }

    private function createCodeRecord(User $user, string $code): void
    {
        $this->entityManager->createEntity(TwoFactorCode::ENTITY_TYPE, [
            'code' => $code,
            'userId' => $user->getId(),
            'method' => 'Sms',
            'attemptsLeft' => $this->getCodeAttemptsCount(),
        ]);
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }

    private function decrementAttemptsLeft(TwoFactorCode $codeEntity): void
    {
        $codeEntity->decrementAttemptsLeft();

        $this->entityManager->saveEntity($codeEntity);
    }

    private function getCodeAttemptsCount(): int
    {
        return $this->config->get('auth2FASmsCodeAttemptsCount') ?? self::CODE_ATTEMPTS_COUNT;
    }
}

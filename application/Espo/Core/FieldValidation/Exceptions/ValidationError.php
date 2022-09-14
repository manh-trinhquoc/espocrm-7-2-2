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

namespace Espo\Core\FieldValidation\Exceptions;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error\Body;

use Espo\Core\FieldValidation\Failure;
use LogicException;

class ValidationError extends BadRequest
{
    private ?Failure $failure = null;

    public static function createWithBody(string $reason, string $body): self
    {
        $exception = parent::createWithBody($reason, $body);

        if (!$exception instanceof self) {
            throw new LogicException();
        }

        return $exception;
    }

    public static function create(Failure $failure): self
    {
        $exception = self::createWithBody(
            'validationFailure',
            Body::create()
                ->withMessageTranslation('validationFailure', null, [
                    'field' => $failure->getField(),
                    'type' => $failure->getType(),
                ])
                ->encode()
        );

        $exception->failure = $failure;

        return $exception;
    }

    public function getFailure(): Failure
    {
        if (!$this->failure) {
            throw new LogicException();
        }

        return $this->failure;
    }
}

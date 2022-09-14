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

namespace Espo\Entities;

use Espo\Core\Entities\Person;

use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;

class User extends Person
{
    public const ENTITY_TYPE = 'User';

    public const ATTRIBUTE_TYPE = 'type';

    public const ATTRIBUTE_IS_ACTIVE = 'isActive';

    public const LINK_ACCOUNTS = 'accounts';

    public const LINK_CONTACT = 'contact';

    public const LINK_PORTALS = 'portals';

    public const LINK_TEAMS = 'teams';

    public const LINK_DEFAULT_TEAM = 'defaultTeam';

    public const LINK_ROLES = 'roles';

    public const LINK_PORTAL_ROLES = 'portalRoles';

    public const TYPE_PORTAL = 'portal';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_REGULAR = 'regular';

    public const TYPE_API = 'api';

    public const TYPE_SUPER_ADMIN = 'super-admin';

    public function isActive(): bool
    {
        return (bool) $this->get('isActive');
    }

    /**
     * @deprecated Use `isPortal`.
     */
    public function isPortalUser(): bool
    {
        return $this->isPortal();
    }

    public function getType(): ?string
    {
        return $this->get('type');
    }

    /**
     * Is regular user.
     */
    public function isRegular(): bool
    {
        return $this->getType() === self::TYPE_REGULAR ||
            ($this->has('type') && !$this->getType());
    }

    /**
     * Is admin, super-admin or system user.
     */
    public function isAdmin(): bool
    {
        return $this->getType() === self::TYPE_ADMIN ||
            $this->isSystem() ||
            $this->isSuperAdmin();
    }

    /**
     * Is portal user.
     */
    public function isPortal(): bool
    {
        return $this->getType() === self::TYPE_PORTAL;
    }

    /**
     * Is API user.
     */
    public function isApi(): bool
    {
        return $this->getType() === self::TYPE_API;
    }

    /**
     * Is system user.
     */
    public function isSystem(): bool
    {
        return $this->getType() === self::TYPE_SYSTEM;
    }

    /**
     * Is super-admin user.
     */
    public function isSuperAdmin(): bool
    {
        return $this->getType() === self::TYPE_SUPER_ADMIN;
    }

    public function getRoles(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('roles');
    }

    public function getDefaultTeam(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('defaultTeam');
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('teams');
    }

    /**
     * @return string[]
     */
    public function getTeamIdList(): array
    {
        /** @var string[] */
        return $this->getLinkMultipleIdList('teams');
    }

    public function setDefaultTeam(?Link $defaultTeam): self
    {
        $this->setValueObject('defaultTeam', $defaultTeam);

        return $this;
    }

    public function setTeams(LinkMultiple $teams): self
    {
        $this->setValueObject('teams', $teams);

        return $this;
    }

    public function setRoles(LinkMultiple $roles): self
    {
        $this->setValueObject('roles', $roles);

        return $this;
    }

    public function loadAccountField(): void
    {
        if ($this->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $this->get('contactId'));

            if ($contact && $contact->get('accountId')) {
                $this->set('accountId', $contact->get('accountId'));
                $this->set('accountName', $contact->get('accountName'));
            }
        }
    }

    public function setTitle(?string $title): self
    {
        $this->set('title', $title);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->get('title');
    }

    public function getUserName(): ?string
    {
        return $this->get('userName');
    }

    public function getAuthMethod(): ?string
    {
        return $this->get('authMethod');
    }

    public function getContactId(): ?string
    {
        return $this->get('contactId');
    }

    public function getContact(): ?Link
    {
        /** @var ?Link $value */
        $value = $this->getValueObject('contact');

        return $value;
    }

    /**
     * Get a portal ID of the currently logged user.
     */
    public function getPortalId(): ?string
    {
        return $this->get('portalId');
    }

    public function getAccounts(): LinkMultiple
    {
        /** @var LinkMultiple $value */
        $value = $this->getValueObject('accounts');

        return $value;
    }

    /**
     * @return ?string
     */
    protected function _getName()
    {
        if (!$this->hasInContainer('name') || !$this->getFromContainer('name')) {
            if ($this->get('userName')) {
                return $this->get('userName');
            }
        }

        return $this->getFromContainer('name');
    }

    /**
     * @return bool
     */
    protected function _hasName()
    {
        if ($this->hasInContainer('name')) {
            return true;
        }

        if ($this->has('userName')) {
            return true;
        }

        return false;
    }
}

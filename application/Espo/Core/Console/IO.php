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

namespace Espo\Core\Console;

use RuntimeException;

use const STDOUT;
use const PHP_EOL;

/**
 * Input/Output methods.
 */
class IO
{
    /**
     * @var int<0, 255>
     */
    private int $exitStatus = 0;

    /**
     * Write a string to output.
     */
    public function write(string $string): void
    {
        fwrite(STDOUT, $string);
    }

    /**
     * Write a string followed by the current line terminator to output.
     */
    public function writeLine(string $string): void
    {
        fwrite(STDOUT, $string . PHP_EOL);
    }

    /**
     * Read a line from input. A string is trimmed.
     */
    public function readLine(): string
    {
        $resource = fopen('php://stdin', 'r');

        if ($resource === false) {
            throw new RuntimeException("Could not open stdin.");
        }

        $readString = fgets($resource);

        if ($readString === false) {
            $readString = '';
        }

        $string = trim($readString);

        fclose($resource);

        return $string;
    }

    /**
     * Set exit-status.
     *
     * @param int<0, 255> $exitStatus
     *   - `0` - success;
     *   - `1` - error;
     *   - `127` - command not found;
     */
    public function setExitStatus(int $exitStatus): void
    {
        $this->exitStatus = $exitStatus;
    }

    /**
     * Get exit-status.
     *
     * @return int<0, 255>
     */
    public function getExitStatus(): int
    {
        return $this->exitStatus;
    }
}

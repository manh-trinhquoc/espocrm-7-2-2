<?php

namespace Espo\Modules\HelloWorld\Controllers;

use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\Api\Request;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\AdminNotificationManager;
use Espo\Core\Utils\SystemRequirements;
use Espo\Core\Utils\ScheduledJob;

use Espo\Core\Upgrades\UpgradeManager;

use Espo\Entities\User;

class Test
{
    public function getActionTest()
    {
        return 'thí nghiệm Espo\Modules\HelloWord\Controllers@getActionTest';
    }

    public function getActionTest2(Request $request, $name)
    {
        $class = get_class_methods($request);
        // var_dump($class);
        var_dump($name);
        die;
        return 'thí nghiệm Espo\Modules\HelloWord\Controllers@getActionTest2 với $name:' . $name;
    }
}

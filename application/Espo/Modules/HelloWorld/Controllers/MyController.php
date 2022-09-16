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

class MyController
{
    public function getActionDoSomething($id)
    {
        var_dump('aaaa');
        echo 'hhhhh';
        // die;
        return 'thí nghiệm Espo\Modules\HelloWord\Controllers@getActionDoSomething" với $id:' . $id['id'];
    }
}

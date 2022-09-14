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

define('controllers/import', ['controllers/record'], function (Dep) {

    return Dep.extend({

        defaultAction: 'index',

        checkAccessGlobal: function () {
            if (this.getAcl().checkScope('Import')) {
                return true;
            }

            return false;
        },

        checkAccess: function () {
            if (this.getAcl().checkScope('Import')) {
                return true;
            }

            return false;
        },

        actionIndex: function (o) {
            o = o || {};

            var step = null;

            if (o.step) {
                step = parseInt(step);
            }

            var formData = null;
            var fileContents = null;

            if (this.storedData) {
                formData = this.storedData.formData;
                fileContents = this.storedData.fileContents;
            }

            if (!formData) {
                step = null;
            }

            formData = formData || o.formData;

            this.main('views/import/index', {
                step: step,
                formData: formData,
                fileContents: fileContents,
                fromAdmin: o.fromAdmin,
            }, (view) => {
                this.listenTo(view, 'change', () => {
                    this.storedData = {
                        formData: view.formData,
                        fileContents: view.fileContents,
                    };
                });

                this.listenTo(view, 'done', () => {
                    delete this.storedData;
                });

                view.render();
            });
        },

    });
});

<?php
/**
  * Copyright (c) Enalean, 2016 - 2017. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\Svn\Admin;


use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryRegexpBuilder;
use DataAccessObject;
use Project;

class MailNotificationDao extends DataAccessObject {

    private $regexp_builder;

    public function __construct($da, RepositoryRegexpBuilder $regexp_builder) {
        parent::__construct($da);
        $this->regexp_builder = $regexp_builder;
    }

    public function searchByRepositoryId($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql = "SELECT *
                FROM plugin_svn_notification
                WHERE repository_id=$repository_id";

        return $this->retrieve($sql);
    }

    public function deleteByNotificationId($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "DELETE n.*, u.*, g.*
                FROM plugin_svn_notification AS n
                    LEFT JOIN plugin_svn_notification_users AS u ON (n.id = u.notification_id)
                    LEFT JOIN plugin_svn_notification_ugroups AS g ON (n.id = g.notification_id)
                WHERE id = $notification_id";

        return $this->update($sql);
    }

    public function create(MailNotification $mail_notification) {
        $mailing_list  = $this->da->quoteSmart($mail_notification->getNotifiedMails());
        $path          = $this->da->quoteSmart($mail_notification->getPath());
        $repository_id = $this->da->escapeInt($mail_notification->getRepository()->getId());

        $query = "REPLACE INTO plugin_svn_notification
                    (repository_id, mailing_list, svn_path)
                  VALUES
                    ($repository_id, $mailing_list, $path)";

        return $this->updateAndGetLastId($query);
    }

    public function updateByNotificationId(MailNotification $email_notification)
    {
        $notification_id = $this->da->escapeInt($email_notification->getId());
        $new_path        = $this->da->quoteSmart($email_notification->getPath());
        $mailing_list    = $this->da->quoteSmart($email_notification->getNotifiedMails());

        $sql = "UPDATE plugin_svn_notification
                SET svn_path = $new_path, mailing_list = $mailing_list
                WHERE id = $notification_id";

        return $this->update($sql);
    }

    public function searchByPath($repository_id, $path) {
        $repository_id        = $this->da->escapeInt($repository_id);
        $sub_paths_expression = '';
        $pattern_matcher      = $this->regexp_builder->generateRegexpFromPath($path, $this->da);

        if ($pattern_matcher !== '') {
            $pattern_matcher      = $this->da->quoteSmart($pattern_matcher);
            $sub_paths_expression = "OR svn_path RLIKE $pattern_matcher";
        }

        $query = "SELECT *
                    FROM plugin_svn_notification
                    WHERE repository_id = $repository_id
                    AND (svn_path = '/' $sub_paths_expression)
                    ";

        return $this->retrieve($query);
    }

    public function searchByPathStrictlyEqual($repository_id, $path)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $path          = $this->da->quoteSmart($path);

        $query = "SELECT *
                    FROM plugin_svn_notification
                    WHERE repository_id = $repository_id
                    AND svn_path = $path
                    ";

        return $this->retrieve($query);
    }

    public function deleteEmptyNotificationsInProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE notif.*
                FROM plugin_svn_notification AS notif
                    INNER JOIN plugin_svn_repositories AS repo ON (repo.id = notif.repository_id AND repo.project_id = $project_id)
                    LEFT JOIN plugin_svn_notification_users AS users ON (notif.id = users.notification_id)
                    LEFT JOIN plugin_svn_notification_ugroups AS ugroups ON (notif.id = ugroups.notification_id)
                WHERE IFNULL(notif.mailing_list, '') = ''
                    AND users.notification_id IS NULL
                    AND ugroups.notification_id IS NULL
                ";

        return $this->update($sql);
    }

    public function searchById($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);
        $sql = "SELECT *
                FROM plugin_svn_notification
                WHERE id=$notification_id";

        return $this->retrieve($sql);
    }

    public function updateGloballyForRepository($repository_id, array $new_email_notification)
    {
        $this->da->startTransaction();

        $repository_id = $this->da->escapeInt($repository_id);

        if (! $this->deleteByRepositoryId($repository_id)) {
            $this->da->rollback();
            return false;
        }

        foreach($new_email_notification as $notification) {
            if (! $this->create($notification)) {
                $this->da->rollback();
                return false;
            }
        }

        return $this->da->commit();
    }

    private function deleteByRepositoryId($repository_id)
    {
        $sql = "DELETE FROM plugin_svn_notification
                WHERE repository_id = $repository_id";

        return $this->update($sql);
    }
}

<?php

require_once dirname(__FILE__) . '/../extensions/gdrive/GDriveForYii.php';

class GDriveCommand extends CronConsoleCommand
{
    private function getRandomDriveClientId()
    {
        $google_accounts = Yii::app()->db->createCommand('SELECT id FROM google_drive_account')->queryAll(true);
        $google_account = $google_accounts[array_rand($google_accounts)];
        return $google_account['id'];
    }

    private function getDriveClient($acc_id = false)
    {
        /**
         * @var GoogleDriveAccount $model
         */
        $model = GoogleDriveAccount::model()->findByPk($acc_id);
        if (!$model) {
            return false;
        }

        return new GDrive(
            $model->app_name,
            $model->client_secret,
            $model->getTokenFilePath()
        );
    }

    /**
     * Checking availibility of access token. If it does not exist get link for obtain token.
     */
    public function actionIndex($acc_id = false)
    {
        $this->logger->insert("checking gApi access token...");

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        if (!$gd->getAccessToken()) {
            $this->logger->insert("token not found. auth url:");
            $this->logger->insert($gd->getAuthUrl());
            $this->logger->insert("");
        }
        else{
            $this->logger->insert("token found.");
        }
    }

    /**
     * Get info about free space on Google Drive.
     */
    public function actionGetFreeSpace($acc_id = false)
    {
        $this->logger->insert("getting GDrive space information...");

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        $space = $gd->getFreeSpace();
        if ($space !== false) {
            $this->logger->insert(sprintf("total: %.2f Mb; used: %.2f Mb; free: %.2f Mb", $space['total']/1048576, $space['usedAggregate']/1048576, $space['free']/1048576));
        }
    }

    /**
     * Obtain and save access token by code from account.
     *
     * @param $acc_id
     * @param $code
     */
    public function actionSetCode($acc_id, $code)
    {
        $this->logger->insert("trying to get gApi access token...");

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        if ($token = $gd->setAuthCode($code)) {
            $this->logger->insert("gApi access token received and saved!");
        }
    }

    /**
     * Upload test file, sharing it for all, and gel link for download
     */
    public function actionTestFileUpload($acc_id = false)
    {
        $this->logger->insert("trying to upload small sample file...");

        if (!$acc_id) $acc_id = $this->getRandomDriveClientId();

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        $fileId = $gd->uploadFile('test.dat', 'test');

        if ($fileId === false) {
            $this->logger->insert("upload failed");
            return;
        }

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        if ($gd->shareFile($fileId)) {
            $link = $gd->createLink($fileId);
            $this->logger->insert("upload done. id: {$fileId} link: {$link}");
        }
        else {
            $this->logger->insert("sharing failed");
        }
    }

    /**
     * Remove file from drive by file_id & account_id
     *
     * @param $acc_id
     * @param $fileId
     */
    public function actionTestFileDelete($acc_id, $fileId)
    {
        $this->logger->insert("trying to delete file...");

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        $unShareRes = $gd->unShareFile($fileId);

        if ($unShareRes !== true)
            $this->logger->insert("remove sharing permissions failed: " . $unShareRes);

        $removeRes = $gd->removeFile($fileId);

        if ($removeRes !== true) {
            $this->logger->insert("remove file failed: " . $removeRes);
        }
        else {
            $this->logger->insert("file removed.");
        }
    }

    /**
     * Get list of files on GD account
     *
     * @param $acc_id
     * @param int $count
     * @param string $sort
     */
    public function actionListFiles($acc_id, $count = 100, $sort = '')
    {
        $this->logger->insert("trying to list files...");

        $gd = $this->getDriveClient($acc_id);
        if ($gd === false) {
            $this->logger->insert("invalid gdrive client");
            return;
        }

        $list = $gd->listFiles($count, $sort);
        if ($list === false) {
            $this->logger->insert("no files");
        }
        else {
            foreach($list as $file) {
                $this->logger->insert($file->getCreatedDate() . " : " . $file->getId() . " : " . $file->getTitle());
            }
        }
    }

    /**
     * Clear Google Drive by time routine
     *
     * @param int $count        кол-во файлов для проверки (1-1000)
     * @param int $timeout      время жизни файла в секундах
     */
    public function actionClean($count = 500, $timeout = 1800)
    {
        $this->logger->insert("trying to clean {$count} files in each GDrive account older than {$timeout} seconds...");

        $cnt = 0;

        $gdAccs = GoogleDriveAccount::model()->findAll();
        foreach ($gdAccs as $gdAcc) {
            /**
             * @var GoogleDriveAccount $gdAcc
             */
            if ($gdAcc->getStatus() != GoogleDriveAccount::STATUS_OK) {
                continue;
            }

            $gd = $gdAcc->getGDrive();
            try {
                $list = $gd->listFiles($count, 'createdDate');
                if ($list === false) {
                    $this->logger->insert('GDrive #' . $gdAcc->id . ' no files');
                } else {
                    $gd->setUseBatch();
                    $batch = $gd->createBatch();
                    foreach ($list as $file) {
                        $fileId = $file->getId();
                        $created = $file->getCreatedDate();

                        $elapsed = time() - strtotime($created);

                        if (!$timeout || $elapsed > $timeout) {
                            $delR = $gd->removeFile($fileId);
                            if ($delR) {
                                $batch->add($delR);
                                $cnt++;
                            } else {
                                $this->logger->insert('GDrive #' . $gdAcc->id . ' ' . $fileId . " : " . $created . " : " . $file->getTitle());
                            }
                        }
                    }
                    $batch->execute();
                }
            } catch (Exception $e) {
                $this->logger->insert("exception: " . get_class($e) . ": " . $e->getCode() . " " . $e->getMessage());
            }

            $this->actionGetFreeSpace($gdAcc->id);
        }

        $this->logger->insert("done. {$cnt} files removed.");
    }
}

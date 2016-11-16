<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf;

use Eccube\Application;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * @var string
     */
    private $logoName = 'logo.png';

    /**
     * @var string
     */
    private $logoPath;

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        $this->logoPath = __DIR__.'/Resource/template/'.$this->logoName;
    }

    /**
     * Install.
     *
     * @param array       $config
     * @param Application $app
     */
    public function install($config, $app)
    {
        // Backup logo.
        $this->backupLogo($app['config']);
    }

    /**
     * Uninstall.
     *
     * @param array       $config
     * @param Application $app
     */
    public function uninstall($config, $app)
    {
        // Remove temp
        $this->removeLogo($app['config']);

        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code'], 0);
    }

    /**
     * Enable.
     *
     * @param array       $config
     * @param Application $app
     */
    public function enable($config, $app)
    {
        // Backup logo.
        $this->backupLogo($app['config']);

        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
    }

    /**
     * Disable.
     *
     * @param array       $config
     * @param Application $app
     */
    public function disable($config, $app)
    {
    }

    /**
     * Update.
     *
     * @param array       $config
     * @param Application $app
     */
    public function update($config, $app)
    {
        $arrConfig = $app['config'];
        // Rollback to old logo
        $this->rollBackLogo($arrConfig);

        // Update
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
    }

    /**
     * Backup logo before update.
     *
     * @param array $config
     */
    private function backupLogo($config)
    {
        $file = new Filesystem();
        if (!file_exists($this->logoPath)) {
            return;
        }
        $file->copy($this->logoPath, $config['template_realdir'].'/'.$this->logoName, true);
    }

    /**
     * Remove logo.
     *
     * @param array $config
     */
    private function removeLogo($config)
    {
        $file = new Filesystem();
        $file->remove($config['template_realdir'].'/'.$this->logoName);
    }

    /**
     * Roll back to old logo.
     *
     * @param array $config
     */
    private function rollBackLogo($config)
    {
        $file = new Filesystem();
        $sourcePath = $config['template_realdir'].'/'.$this->logoName;
        if (!file_exists($sourcePath)) {
            return;
        }
        $file->copy($sourcePath, $this->logoPath, true);
    }
}

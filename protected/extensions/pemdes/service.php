<?php
namespace Extensions;

class PemdesService
{
    protected $basePath;
    protected $themeName;
    protected $adminPath;
    protected $tablePrefix;

    public function __construct($settings = null)
    {
        $this->basePath = (is_object($settings))? $settings['basePath'] : $settings['settings']['basePath'];
        $this->themeName = (is_object($settings))? $settings['theme']['name'] : $settings['settings']['theme']['name'];
        $this->adminPath = (is_object($settings))? $settings['admin']['path'] : $settings['settings']['admin']['path'];
        $this->tablePrefix = (is_object($settings))? $settings['db']['tablePrefix'] : $settings['settings']['db']['tablePrefix'];
    }
    
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_pemdes_surat_permohonan` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(128) DEFAULT NULL,
          `slug` varchar(128) DEFAULT NULL,
          `description` text DEFAULT NULL ,
          `configs` text DEFAULT NULL,
          `status` int(11) DEFAULT '1',
          `created_at` datetime NOT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_pemdes_request_surat` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(64) NOT NULL,
          `nik` varchar(32) NOT NULL,
          `email` varchar(32) DEFAULT NULL,
          `birth_place` varchar(128) DEFAULT NULL,
          `birth_date` date DEFAULT NULL,
          `images` varchar(256) DEFAULT NULL,
          `handphone` varchar(16) DEFAULT NULL,
          `notes` text,
          `status` int(11) DEFAULT '0',
          `created_at` datetime DEFAULT NULL,
          `processed_at` datetime DEFAULT NULL,
          `processed_by` int(11) DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        COMMIT;";

        $sql = str_replace(['{tablePrefix}'], [$this->tablePrefix], $sql);
        
        $model = new \Model\OptionsModel();
        $install = $model->installExt($sql);

        return $install;
    }

    public function uninstall()
    {
        return true;
    }
}

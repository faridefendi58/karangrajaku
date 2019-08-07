<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class SuratPermohonanModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_pemdes_surat_permohonan';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['title', 'required'],
        ];
    }

    public function getItem($data) {
        if (isset($data['id'])) {
            $model = self::model()->findByPk($data['id']);
        }

        if (isset($data['slug'])) {
            $model = self::model()->findByAttributes(['slug' => $data['slug']]);
        }

        if ($model instanceof \RedBeanPHP\OODBBean) {
            return $model;
        }

        return false;
    }

    public function getItems() {
        $sql = "SELECT i.*   
        FROM {tablePrefix}ext_translation i 
        WHERE 1";

        $sql .= " ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        return $rows;
    }
}

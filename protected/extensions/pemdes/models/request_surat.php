<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class RequestSuratModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_pemdes_request_surat';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['name, nik', 'required'],
        ];
    }

    public function getItems($data = []) {
        $sql = "SELECT t.*, p.title, p.slug 
        FROM {tablePrefix}ext_pemdes_request_surat t 
        LEFT JOIN {tablePrefix}ext_pemdes_surat_permohonan p ON p.id = t.surat_permohonan_id
        WHERE 1";

        $params = [];
        if (isset($data['status'])) {
            $sql .= ' AND t.status =:status';
            $params['status'] = $data['status'];
        }

        $sql .= " ORDER BY t.created_at DESC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getItem($id) {
        $sql = "SELECT t.*, p.title, p.slug 
        FROM {tablePrefix}ext_pemdes_request_surat t 
        LEFT JOIN {tablePrefix}ext_pemdes_surat_permohonan p ON p.id = t.surat_permohonan_id
        WHERE t.id =:id";

        $params = ['id' => $id];

        $sql .= " ORDER BY t.created_at DESC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row;
    }

    public function getStatistic($data = []) {
        $sql = "SELECT t.id, t.status, p.title, p.slug, SUM(t.id) AS total 
        FROM {tablePrefix}ext_pemdes_request_surat t 
        LEFT JOIN {tablePrefix}ext_pemdes_surat_permohonan p ON p.id = t.surat_permohonan_id
        WHERE 1";

        $params = [];
        if (isset($data['status'])) {
            $sql .= ' AND t.status =:status';
            $params['status'] = $data['status'];
        }

        $sql .= " GROUP BY t.status ORDER BY t.created_at DESC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}

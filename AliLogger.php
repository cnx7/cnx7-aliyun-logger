<?php

namespace cnx7\AliLogger;

require_once dirname(__FILE__) . '/Log_Autoload.php';

class AliLogger {
    public $endpoint;
    public $accessKeyId;
    public $accessKey;
    public $project;
    public $logstore;
    public $token;
    public $client;
    public $logitems = [];
    public $falg = 0;

    /**
     * AliLogger constructor.
     * @param array $params
     */
    public function __construct($params = []) {
        $this->accessKey = $params['accessKey'];
        $this->accessKeyId = $params['accessKeyId'];
        $this->endpoint = $params['endpoint'];
        $this->project = $params['project'];
        $this->logstore = $params['logstore'];
        $this->topic = isset($params['topic']) ? $params['topic'] : '';
        $this->client = new \Aliyun_Log_Client($this->endpoint, $this->accessKeyId, $this->accessKey, $this->token);
    }

    /**
     * 批量添加开始
     */
    public function begin() {
        $this->falg = 1;
    }

    /**
     * 提交到SLS
     * @return \Aliyun_Log_Models_PutLogsResponse
     * @throws \Exception
     */
    public function commit() {

        $request = new \Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore, $this->topic, null, $this->logitems);
        $this->logitems = array();
        $this->falg = 0;

        try {
            $response = $this->client->putLogs($request);
            return $response;
        } catch (\Aliyun_Log_Exception $ex) {
            throw new \Exception($ex->getMessage());
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * 批量添加取消
     */
    public function rollback() {
        $this->logitems = [];
        $this->falg = 0;
    }

    /**
     * 添加日志 没有批量标记则自动提交
     * @param array $arr
     */
    public function log($arr = []) {
        $logItem = new \Aliyun_Log_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($arr);
        $this->logitems[] = $logItem;
        if ($this->falg === 0) {
            $this->commit();
        }
    }

    /**
     * 查询日志
     * @param $start_time int 开始时间
     * @param $end_time int 结束时间
     * @param $query string 过滤条件
     * @param null $page_num int 页码
     * @param null $page_size int 每页条数
     * @return \Aliyun_Log_Models_GetLogsResponse|null
     *
     * Exp:
     * $query = '*';
     * $start_time = time() - 86400;
     * $end_time = time();
     *
     * $client = new AliLogger($args);
     * $res = $client->search($start_time, $end_time, $query, null, null);
     *
     * $count = $res->getCount();
     * $is_completed = $res->isCompleted();
     * $logs = $res->getLogs();
     *
     * foreach ($logs as $log) var_dump($log->getContents());
     */
    public function search($start_time, $end_time, $query, $page_num = null, $page_size = null) {
        $res = NULL;
        while (is_null($res) || (!$res->isCompleted())) {
            $req = new \Aliyun_Log_Models_GetLogsRequest($this->project, $this->logstore, $start_time, $end_time, $this->topic, $query, $page_size, ($page_num - 1) * $page_size, False);
            $res = $this->client->getLogs($req);
        }

        return $res;
    }

}

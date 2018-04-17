<?php 
namespace doctorjason\mailerqueue;

use Yii;

class MailerQueue extends \yii\swiftmailer\Mailer
{
	// 指定要实例化的类
	public  $messageClass="doctorjason\mailerqueue\Message";


	public $key = 'mails';

    public $db = '1';

    /**
     * [process description]
     *
     * @DateTime 2018-04-18
     *
     * @return   [type]
     */
    public function process()
    {
    	$redis=Yii::$app->redis;

    	if(empty($redis))
    	{
    		throw new \yii\base\InvalidConfigException("redis config not found!");
    	}
    	// 选择库并且取出队列里面的消息
    	if($redis->select($this->db) && $messages=$redis->lrange($this->key,0,-1))
    	{
    		$messageObj=new Message; //实例对象
    		// 遍历队列的消息
    		foreach ($messages as $message) {
    			$message=json_decode($message,true);
    			// 消息没有就抛出异常
    			if( empty($message) || !$this->setMessage($messageObj,$message) )
    			{
    				throw new \yii\web\ServerErrorHttpException("message not found");
    			}

    			// 发送成功就把当前这条消息从队列中压出
    			if($messageObj->send())
    			{
    				$redis->lrem($this->key,-1,json_encode($message));
    			}
    		}
    	}

    	return true; //相关操作成功返回 true
    }
    /**
     * [setMessage 设置邮件的内容]
     *
     * @DateTime 2018-04-18
     *
     * @param    [type] $messageObj
     * @param    [type] $message
     */
 	private function setMessage($messageObj, $message) 
    {
        if (empty($messageObj)) {
            return false;
        }
        if (!empty($message['from']) && !empty($message['to'])) {
            $messageObj->setFrom($message['from'])->setTo($message['to']);
            if (!empty($message['cc'])) {
                $messageObj->setCc($message['cc']);
            }
            if (!empty($message['bcc'])) {
                $messageObj->setBcc($message['bcc']);
            }
            if (!empty($message['reply_to'])) {
                $messageObj->setReplyTo($message['reply_to']);
            }
            if (!empty($message['charset'])) {
                $messageObj->setCharset($message['charset']);
            }
            if (!empty($message['subject'])) {
                $messageObj->setSubject($message['subject']);
            }
            if (!empty($message['html_body'])) {
                $messageObj->setHtmlBody($message['html_body']);
            }
            if (!empty($message['text_body'])) {
                $messageObj->setTextBody($message['text_body']);
            }
            return $messageObj;
        }
        return false;
    }

}
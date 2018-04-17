<?php 

namespace doctorjason\mailerqueue;

use Yii;

class Message extends \yii\swiftmailer\Message 
{
	public function queue()
	{
		/**
		 * 主体  收件人  内容  拼接数组 压入redis队列
		 */
		// 判断redis是否存在
		$redis=Yii::$app->redis;

		if(empty($redis))
		{
			// 抛出不存在配置文件的信息
			throw new \yii\base\invalidConfigException('redis not found in config');
		}

		// 默认0-15 个库
		// 设置邮件存储在那个库里面
		$mailer=Yii::$app->mailer;
		// 判断该配置是否存在,判断redis的库是否可选
		if( empty($mailer) || !$redis->select($mailer->db) )
		{
			throw new \yii\base\InvalidConfigException('db is not defined');
		}

		// 上面都是简单的逻辑判断是否存在配置信息
		// 因为已经继承了父类的 swiftmailer,我们可以用 $this 来获取父类
		// 
		$message=[];
	 	$message['from'] = array_keys($this->from);
		$message['to'] = is_array($this->getTo()) ? array_keys($this->getTo()) : '';
        $message['cc'] = is_array($this->getCc()) ? array_keys($this->getCc()) : '';
        $message['bcc'] = is_array($this->getBcc()) ? array_keys($this->getBcc()) : '';
        $message['reply_to'] =is_array($this->getReplyTo()) ?  array_keys($this->getReplyTo()) : '';
        $message['charset'] = is_array($this->getCharset()) ? array_keys($this->getCharset()) : '';
        $message['subject'] =is_array($this->getSubject()) ?  array_keys($this->getSubject()) : $this->subject;	 	
        // $message['to'] = array_keys($this->getTo());
        // $message['cc'] = array_keys($this->getCc());
        // $message['bcc'] = array_keys($this->getBcc());
        // $message['reply_to'] = array_keys($this->getReplyTo());
        // $message['charset'] = array_keys($this->getCharset());
        // $message['subject'] = array_keys($this->getSubject());
        // 上面是头部信息，
        // 获取邮件内容信息
        // 获取邮件的子信息
        $parts=$this->getSwiftMessage()->getChildren();

        if(!is_array($parts) || !sizeof($parts))
        {
        	$parts=[$this->getSwiftMessage()];
        }

        foreach ($parts as $part) {
        	
        	if(!$part instanceof \Swift_Mime_Attachment)
        	{
        		//
        		switch ($part->getContentType()) {
    			 	case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
        		}

        		if (!$message['charset']) {

                    $message['charset'] = $part->getCharset();
                }
        	}
        }
        // 压入 redis 里面
        return $redis->rpush($mailer->key,json_encode($message));
	}
}

?>
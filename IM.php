<?php

if(file_exists(dirname(__FILE__)."/MyTable.php") )
	include_once dirname(__FILE__)."/MyTable.php";
if(file_exists(dirname(__FILE__)."/BaseModel.php") )	
	include_once dirname(__FILE__)."/BaseModel.php";
	
interface IIM
{
	/**
	* 发送一个消息
	* 返回一个Message对象
	*/
	function send($arr);
	
	/**
	* 获取符合条件的消息
	*/
	function receive($arr);
	
	/**
	* 更新消息的状态
	*/
	function update($arr);
	
	/**
	* 网聊模式中发送一个消息
	*/
	function webTalk($arr);
	
	/**
	* 将array或object转为JSON格式数据
	*/
	function toJSON($arr);
}


class MySqlIM implements IIM
{
	private $model ;
	
	private function arrayToMessage( $arr )
	{
		$msg = new Message();
		$msg->text = $arr['text'];
		$msg->sender_id = $arr['senderId'];//$_GET['senderId'];
		$msg->recipient_id = $arr['recipientId'];//$_GET['recipientId'];
		$msg->sender_name = $arr['sender_name'];
		$msg->recipient_name = $arr['recipient_name'];
		return $msg;
	}
	
	function MySqlIM()
	{
		$this->model = new BaseModel("gelivable");
		//$this->model->printSQL = 1==1;
	}
	
	function send( $data )
	{
		return $this->model->save($this->arrayToMessage($data) , "message_id");
	}
	
	function receive( $data )
	{
		if( isset($data['senderId']) )
		{
			$json = $this->model->getModelList(new Message() , " and ((`recipient_id`=".$data['senderId']." and `is_read`=0 ) or ( ".($data['messageId'] < 0 ?"false":"`message_id` > " .$data['messageId'])." and `recipient_id` = -1 ))");
			
			$user = new Users();
			$user->last_date = date ( "Y-m-d H:i:s" ) ;
			$this->model->update($user , " and id = " .$data['senderId']);
		}
		else
		{
			$json = $this->model->getModelList(new Message() , "  and ( ".($data['messageId'] < 0 ?"false":"`message_id` > " .$data['messageId'])." and `recipient_id` = -1 )");
		}
		
		return $json;
	}
	
	function getMessage($data)
	{
		
	}
	
	function update( $data )
	{
		if( is_array($data['message_id']) )
		{
			$condition = " and `message_id` in ( ";
			
			foreach( $data['message_id'] as $message_id )
			{
				$condition .= $message_id . ",";
			}
			$condition .= " -1)";
			
			$message = new Message();
			$message->is_read = 1 ;
			$this->model->update( $message , $condition);
		}
	}
	
	function webTalk( $data )
	{
		return $this->model->save($this->arrayToMessage($data) , "message_id");
	}
	
	function toJSON($data)
	{
		$tmp['time'] = time();
		$tmp['message'] = $data;
		if($data['callback'])
		{
			return "try{".$data['callback']."(".json_encode($tmp).");}catch(e){}";
		}
		else 
			return  json_encode($tmp);
	}
}


if( class_exists("SaeKV" )):
class KVIM implements IIM
{
	private $kv ;
	private $model ;
	
	private $KEY_MESSAGE_ID = "im_message_id";
	function KVIM()
	{
		$this->kv = new SaeKV();
		$this->kv->init();
		$this->model = new BaseModel("gelivable");
	}
	
	private function arrayToMessage( $arr )
	{
		$msg = new Message();
		$msg->text = $arr['text'];
		$msg->sender_id = (int)$arr['senderId'];//$_GET['senderId'];
		$msg->recipient_id = (int)$arr['recipientId'];//$_GET['recipientId'];
		$msg->sender_name = $arr['sender_name'];
		$msg->recipient_name = $arr['recipient_name'];
		$msg->message_id = $this->getMeeageId();
		$msg->created_at =date ( "Y-m-d H:i:s" );
		$msg->is_read = 0 ;
		return $msg;
	}
	
	function getMeeageId()
	{
		$id = $this->kv->get($this->KEY_MESSAGE_ID);
		if($id == false)
			$id = 0 ;
		$id++;
		$this->kv->set($this->KEY_MESSAGE_ID , $id);
		return $id;
	}
	function send($data)
	{
		$message = $this->arrayToMessage($data);
		if($this->kv->add($message->recipient_id."_".$message->message_id , $message))
			return $message;
		return null;
	}
	
	function receive($data)
	{
		$messageId = (int)$data['messageId'] ;
		$json ;
		if( isset($data['senderId']) )
		{
                  	$messages = $this->kv->pkrget($data['senderId'],100); 
			foreach( $messages as $k => $v )
			{
                        	if(!is_object($v))continue;
				if( $v->is_read == 0)
				{
					$json[] = $v ;
				}
			}
			
			$user = new Users();
			$user->last_date = date ( "Y-m-d H:i:s" ) ;
			$this->model->update($user , " and id = " .$data['senderId']);
		}
                
                $mssages = $this->kv->pkrget("-1",100);
                foreach( $messages as $k => $v )
                {
                        if( $messageId > 0 && $v->message_id >  $messageId && $v->recipient_id == -1 );
                        {
                                $json[] = $v ;
                        }
                  // if()
                }
		
		return $json;
	}
	function update($data)
	{
        	
		if( is_array($data['message_id']) )
		{
			$messages = $this->kv->pkrget('', 100);
			while (true) 
			{
				end($messages);
				$start_key = key($messages);
				foreach( $messages as $k => $v )
				{
					$messageId = $v->message_id . "";
					if( in_array($messageId , $data['message_id'] ))
					{
                                        	$this->kv->delete($k);
					}
				}
				if ($i < 100) break;
				$messages = $this->kv->pkrget('', 100, $start_key);
			}
			
		}
	}
	function webTalk($data)
	{
		return $this->send($data);
	}
	function toJSON($data)
	{
		$tmp['time'] = time();
		$tmp['message'] = $data;
		if($data['callback'])
		{
			return "try{".$data['callback']."(".json_encode($tmp).");}catch(e){}";
		}
		else 
			return  json_encode($tmp);
	}
        
        private function stringToTimeStamp( $str )
       {
              //日期格式 Y-m-d H:i:s
              $a = explode(" " , $str ) ; // [Y-m-d] , [H:i:s]
              
               $b = $a[1];
               $a = $a[0];
               
               $a = explode("-",$a);
               $b = explode(":",$b);
               
               $t[h] = (int)$b[0];
               $t[m] = (int)$b[1];
               $t[s] = (int)$b[2];
               
               $t[y] = (int)$a[0];
               $t[M] = (int)$a[1];
               $t[d] = (int)$a[2];
               
              return  mktime($t[h] , $t[m] , $t[s] , $t[d] , $t[M] , $t[y]);
       }
}
endif;

?>
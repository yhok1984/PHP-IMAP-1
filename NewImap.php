<?php
/***********************************************************************************
 * 
 * 使用IMAP接收发送邮件
 * 
 * **********************************************************************************/

class NewImap
{
	var $mailserver = '';
	var $port = '993';
	var $ssl = true;
	var $type = 'imap';
	var $path = '';
	var $error = '';

	var $server='';
	var $username='';
	var $password='';
	var $marubox='';

	var $htmlmsg='';
	var $plainmsg='';
	var $charset='UTF-8';
	var $attachments='';
	var $attachments_path = '';

	function __construct($username,$password,$path=''){

		$this->username = $username;
		$this->password = $password;
		if ($path) {
			$this->path = $path;
		}

	}
	
	public function setMailServer($mailserver=''){
		
		if($mailserver){
			$this->mailserver = $mailserver;
		}
		
	}

	public function setPort($port=''){
	
		if($port){
			$this->port = $port;
		}
	
	}
	
	public function setAttachmentsPath($attachments_path){
		
		if ($attachments_path) {
			$this->attachments_path = $attachments_path;
		}
		
	}
	
	public function setCharset($charset){
		
		if ($charset) {
			$this->charset = $charset;
		}
		
	}
	
	public function setSSL($ssl=false){
	
		if($mailserver){
			$this->mailserver = $ssl;
		}
	
	}
	
	public function setPath($path=''){
	
		if($path){
			$this->path = $path;
		}
	
	}
	
	/*
	 * 获取未读邮件编号
	 * */
	public function getUnSeenIds(){
		if(!$this->marubox)
			return false;
		
		return imap_search($this->marubox, 'UNSEEN');
		
	}
	
	/*
	 * 标记邮件为已读
	 * */
	public function setSeen($ids){
		
		if(!$this->marubox)
			return false;
		
		$id_str = '';
		if (is_array($ids)) {
			$id_str = implode(',', $ids);
		}else{
			$id_str = $ids;
		}
		
		return imap_setflag_full($this->marubox, $id_str, "\\Seen");
		
	}

	/*
	 * 连接IMAP
	* */
	public function connect() //Connect To the Mail Box
	{
		if (!$this->mailserver) {
			$this->error = '未指定服务器!';
			return false;
		}
		
		$this->server = '{'.$this->mailserver.':'.$this->port.'/'.$this->type.($this->ssl?'/ssl/novalidate-cert':'').'}'.$this->path;

		$this->marubox=@imap_open($this->server,$this->username,$this->password);

		if(!$this->marubox)
		{
			var_dump(imap_errors());
			exit;
		}
	}

	/*
	 * 关闭IMAP
	* */
	public function close_mailbox() //Close Mail Box
	{
		if(!$this->marubox)
			return false;

		imap_close($this->marubox,CL_EXPUNGE);
	}

	/*
	 * 创建mailbox
	 * */
	public function createMailBox($boxname=''){
		
		if(!$this->marubox)
			return false;
		
		if(!imap_list($this->marubox,'{'.$this->mailserver.'}',$boxname)){
			
			return imap_createmailbox($this->marubox, imap_utf7_encode('{'.$this->mailserver.'}'.$boxname));
			
		}
		
		return true;
		
	}
	
	/*
	 * 获取邮件文件夹列表
	 * */
	public function getMailBoxList(){
		
		if(!$this->marubox)
			return false;
		
		return imap_list($this->marubox,'{'.$this->mailserver.'}','*');
		
	}
	
	/*
	 * 移动邮件到指定mailbox
	 * */
	public function moveMail($boxname,$ids){
		
		if(!$this->marubox)
			return false;
		
		$idList = '';
		if (is_array($ids)) {
			$idList = implode(',', $ids);
		}else{
			$idList = $ids;
		}
		
		return imap_mail_move($this->marubox,$idList,$boxname);
		
	}
	
	/*
	 * 获取mailbox邮件个数
	 * */
	public function getMailBoxNum(){
		
		if(!$this->marubox)
			return false;
		
		return imap_num_msg($this->marubox);
		
	}
	
	/*
	 * 切换mailbox
	 * */
	public function changeMailBox($boxname){
		
		$this->path = str_replace('/', '.', $boxname);
		
		if($this->marubox){
			
			imap_reopen($this->marubox, '{'.$this->mailserver.':'.$this->port.'/'.$this->type.($this->ssl?'/ssl/novalidate-cert':'').'}'.$this->path) or die(implode(", ", imap_errors()));
			
		}else{
			
			$this->connect();
			
		}
		
	}

	/*
	 * 获取单个邮件的头部信息
	* access  Msgno  邮件编号
	* return  头详细信息
	* */
	public function getHeaderInfo($mid)
	{
		if(!$this->marubox)
			return false;

		$mail_header=imap_headerinfo($this->marubox,$mid);
		
		//日期
		$date = date('Y-m-d H:i:s',strtotime($mail_header->date));

		//标题
		$subject_arr = imap_mime_header_decode($mail_header->subject);
		foreach ($subject_arr as $sba){
			if (isset($sba->charset)&&$sba->charset!='default'&&$sba->charset!=$this->charset) {
				$subject .= iconv($sba->charset, $this->charset, $sba->text);				
			}else{
				$subject .= $sba->text;
			}
		}
		
		//to
		$to_arr = $mail_header->to[0];
		$to_personal_arr = imap_mime_header_decode($to_arr->personal);
		foreach ($to_personal_arr as $tpa){
			if (isset($tpa->charset)&&$tpa->charset!='default'&&$tpa->charset!=$this->charset) {
				$to_personal .= iconv($tpa->charset, $this->charset, $tpa->text);
			}else{
				$to_personal .= $tpa->text;
			}
		}
		$to_email = $to_arr->mailbox.'@'.$to_arr->host;
		
		//from
		$from_arr = $mail_header->from[0];
		$from_personal_arr = imap_mime_header_decode($from_arr->personal);
		foreach ($from_personal_arr as $fpa){
			if (isset($fpa->charset)&&$fpa->charset!='default'&&$fpa->charset!=$this->charset) {
				$from_personal .= iconv($fpa->charset, $this->charset, $fpa->text);
			}else{
				$from_personal .= $fpa->text;
			}		
		}
		$from_email = $from_arr->mailbox.'@'.$from_arr->host;
		
		//reply_to
		$reply_to_arr = $mail_header->reply_to[0];
		$reply_to_personal_arr = imap_mime_header_decode($reply_to_arr->personal);
		foreach ($reply_to_personal_arr as $rta){
			if (isset($rta->charset)&&$rta->charset!='default'&&$rta->charset!=$this->charset) {
				$reply_to_personal .= iconv($rta->charset, $this->charset, $rta->text);
			}else{
				$reply_to_personal .= $rta->text;
			}
		}	
		$reply_to_email = $reply_to_arr->mailbox.'@'.$reply_to_arr->host;
		
		//sender
		$sender_arr = $mail_header->sender[0];
		$sender_personal_arr = imap_mime_header_decode($sender_arr->personal);
		foreach ($sender_personal_arr as $spa){
			if (isset($spa->charset)&&$spa->charset!='default'&&$spa->charset!=$this->charset) {
				$sender_personal .= iconv($spa->charset, $this->charset, $spa->text);
			}else{
				$sender_personal .= $spa->text;
			}
		}
		$sender_email = $sender_arr->mailbox.'@'.$sender_arr->host;

		$mail_details=array(
				'date'					=>		$date,
				'subject'				=>		$subject,
				'message_id'			=>		$mail_header->message_id,
				'to_personal'			=>		$to_personal,
				'to_email'				=>		$to_email,
				'from_personal'			=>		$from_personal,
				'from_email'			=>		$from_email,
				'reply_to_personal'		=>		$reply_to_personal,
				'reply_to_email'		=>		$reply_to_email,
				'sender_personal'		=>		$sender_personal,
				'sender_email'			=>		$sender_email,
				'Recent'				=>		$mail_header->Recent,			//R:看过的新邮件，N：没看过的新邮件，''：旧邮件
				'Unseen'				=>		$mail_header->Unseen,			//U:没看过的就邮件,'':新邮件
				'Flagged'				=>		$mail_header->Flagged,			//F：被标记的，''：未标记的
				'Answered'				=>		$mail_header->Answered,			//A：回复了，''：未回复
				'Deleted'				=>		$mail_header->Deleted,			//D:删除的，'':未删除的
				'Draft'					=>		$mail_header->Draft,			//X：草稿，''：不是草稿
				'Msgno'					=>		$mail_header->Msgno,			//邮件编号
				'MailDate'				=>		$mail_header->MailDate,			//发送时间
				'Size'					=>		$mail_header->Size,				//邮件大小
				'udate'					=>		$mail_header->udate,			//时间戳

		);

		return $mail_details;
	}

	/*
	 * 获取单个邮件主题信息
	* */
	public function getBody($mid)
	{
		if(!$this->marubox)
			return false;

		//初始化邮件内容
		$this->htmlmsg = '';
		$this->plainmsg = '';
		$this->attachments = array();
		
		$s = @imap_fetchstructure($this->marubox,$mid);
		
		if (!$s) {
			return  false;
		}else{
			if (!$s->parts)
				// 单部分
				$this->getpart($mid,$s,0);
			 
			else {
				// 多部分
				foreach ($s->parts as $k=>$v)
					$this->getpart($mid,$v,$k+1);
			
			}
			
			//保存附件
			if ($this->attachments&&!empty($this->attachments)) {
				if (!is_dir($this->attachments_path)){
					mkdir($this->attachments_path);
				}
				foreach ($this->attachments as $filename=>$content){
					file_put_contents($this->attachments_path.$filename, $content,FILE_APPEND);
				}
			}
			
			return $this->htmlmsg?$this->htmlmsg:$this->plainmsg;
		}	

	}

	/*
	 * 解析结构
	* */
	private function getpart($mid,$p,$partno) {
		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
		// type :0 文字 text 1 复合 multipart 2 信息 message 3 程序 application 4 声音 audio 5 图形 image 6 影像 video 7 其它 other

		// 判断数据是否为简单结构,返回数据xi
		$data = ($partno)?
		imap_fetchbody($this->marubox,$mid,$partno):  // multipart
		imap_body($this->marubox,$mid);  // simple

		// 解码
		switch ($p->encoding){
			case 0:
				$data = imap_8bit($data);
				break;
			case 1:
				$data = imap_8bit($data);
				break;
			case 2:
				$data = imap_binary($data);
				break;
			case 3:
				$data = imap_base64($data);
				break;
			case 4:
				$data = quoted_printable_decode($data);
				break;
		}

		// 参数数组
		$params = array();
		
		if ($p->parameters)
		foreach ($p->parameters as $x)
			$params[strtolower($x->attribute)] = $x->value;

		if ($p->dparameters)
		foreach ($p->dparameters as $x)
			$params[strtolower($x->attribute)] = $x->value;

		// 附件
		if ($params['filename'] || $params['name']) {
			$temp_filename = ($params['filename'])? $params['filename'] : $params['name'];
			$filename_arr = imap_mime_header_decode($temp_filename);
			foreach ($filename_arr as $fa){
				if (isset($fa->charset)&&$fa->charset!='default'&&$fa->charset!=$this->charset) {
					$filename .= iconv($fa->charset, $this->charset, $fa->text);
				}else{
					$filename .= $fa->text;
				}
			}
			//更换名字，防止重复
			$temp_name_arr = explode(".", $filename);
			$tn = count($temp_name_arr);
			$new_name = "ycj".time();
			for ($iii=0;$iii<$tn-1;++$iii){
				$new_name .= $temp_name_arr[$iii];
			}
			$new_name .= ".".$temp_name_arr[$tn-1];			
			$this->attachments[$new_name] = $data;
		}

		// 文本
		if ($p->type==0 && $data) {
			
			//转码
			if (isset($params['charset'])&&$params['charset']!='default'&&$params['charset']!=$this->charset) {
				$data = iconv($params['charset'], $this->charset, $data);
			}
			
			//处理格式
			$array1 = array("'","=3D","=20");
			$array2 = array("\'","="," ");
			$data = str_replace($array1,$array2, $data);
			$data = preg_replace("'=\R'","", $data);

			if (strtolower($p->subtype)=='plain'){			
				$data = str_replace(PHP_EOL, '<br/>', $data);
				$this->plainmsg .= trim($data) ."\n";		
			}else{
				$this->htmlmsg .= $data ."<br/>";				
			}
			
		}elseif ($p->type==2 && $data) {
			
			//转码
			if (isset($params['charset'])&&$params['charset']!='default'&&$params['charset']!=$this->charset) {
				$data = iconv($params['charset'], $this->charset, $data);
			}
			
			$this->plainmsg .= $data."\n\n";
			
		}

		if ($p->parts) {
			foreach ($p->parts as $key=>$p2)
				$this->getpart($mid,$p2,$partno.'.'.($key+1));  // 1.2, 1.2.1, etc.
		}
	}
	
	/*
	 * 删除邮件
	* */
	public function deleteMails($mid) // Delete That Mail
	{
		if(!$this->marubox)
			return false;
	
		imap_delete($this->marubox,$mid);
	}

}








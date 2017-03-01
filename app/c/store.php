<?php
class store extends base{

  function __construct()
  {
    parent::__construct();
    global $app_id;
    
    $this->check();
    $this->loadapp();
    $this->m = load('m/eku');
       $this->menu = array(
    'index'=>'出入库',
    'stock'=>'库存',
    'history'=>'明细',
    'calculate'=>'统计',
    'help'=>'帮助',
    'account'=>'账户'
   );
   
    $this->app = load('m/app')->get($app_id);
    if( $this->app['admin'] == $this->u['id'] ) $this->menu['sys'] ='设置';
  }

  function index()
  {
    global $app_id;
    $conf = array('pid'=>'required');//,'pid'=>'required','pname'=>'required','unit'=>'required','num'=>'required','action'=>'required','action_label'=>'required','alert_level'=>'required','kuwei'=>'required','datetime'=>'required','doer'=>'required','remark'=>'required','category'=>'required','balance'=>'required','cur_balance'=>'required',);
    $err = validate($conf);
    if ( $err === TRUE) {
      $p = $this->m->getbypid($_POST['pid']);
      
      if($p) {
        $p['cur_balance'] = 1;
        $p['datetime'] = $_POST['datetime']?strtotime($_POST['datetime']):time();
        $p['doer'] = $this->u['name'];
        $p['num'] = $_POST['action']*$_POST['num'];
        $p['balance'] += $_POST['action']*$_POST['num'];
        $p['action_label'] = $_POST['action_label'];
        $p['remark'] = $_POST['remark'];
        $this->m->update($p['id'],array('cur_balance'=>0));
        $this->m->add($p);
      }
      else {
        $_POST['cur_balance'] = 1;
        $_POST['datetime'] = isset($_POST['datetime'])?strtotime($_POST['datetime']):time();
        $_POST['doer'] = $this->u['name'];
        $_POST['balance'] = $_POST['action']*$_POST['num'];
        $_POST['app_id'] = $app_id;
        $this->m->add();
      }
      redirect(BASE.'store/','发布成功！');
    }
    else {
      $param['val'] = $_POST; 
      $param['err'] = $err;
      $param['app'] = $app = load('m/app')->get($app_id);
      $param['app']['data'] = json_decode($app['data'],true);
      $param['eku'] = $this->m->eku();
      $this->display('v/store/action',$param);    
    }
  }    
  
  
  function stock($pcurrent)
  {
    $tot = $this->m->count();
    $psize = 3000;
//    $pcurrent = isset( $_POST['p'] )? $_POST['p']:0;
    $param['pagination'] = pagination($tot , $pcurrent , $psize ,BASE.'/stock/stock/');
    $param['records'] = $this->m->eku();
    $this->display('v/store/stock',$param);    
  }
  

  function history()
  {
    global $app_id;
    $where = " and app_id='".$app_id."'";
    $str = '';
    $param = array();

    $param['category'] = $category =  addslashes(isset($_POST['category'])?$_POST['category']:'');
    if($category){
      $where.="  and category = '$category'";
      $str.= '&category='.$category;
    }
    /*
    $param['customer'] = $customer =  addslashes($_POST['customer']?$_POST['customer']:'');
    if($customer){
      $where.="  and customer = '$customer'";
      $str.= '&customer='.$customer;
    }
    */
     
    $sdate = $param['sdate'] =  isset($_POST['sdate'])?$_POST['sdate']:date('Y-m-d');
    $sd = strtotime($sdate);
    $where.="  and datetime > $sd";
    $str.= '&sdate='.$sdate;
    

    $param['edate'] = $edate = isset($_POST['edate'])?$_POST['edate']:date('Y-m-d');
    $ed = strtotime($edate) + 24* 3600;
    $where.="  and datetime < $ed";
    $str.= '&edate='.$edate;
 
    
    $tot = $this->m->count($where);
    $psize = 300;
    $pcurrent = isset( $_GET['p'] )? $_GET['p']:0;
    $param['pagination'] = pagination($tot , $pcurrent , $psize ,BASE.'stock/history&'.$str.'&p=');
    $param['records'] = $this->m->get($where.' order by datetime desc' , $pcurrent ,  $psize);
    $this->display('v/store/history',$param);    
  }
  
  function history_view($pid)
  {
    $pid = mysql_real_escape_string($pid);
		$where = $str = '';
		if($_POST['sdate']){
			$sdate = strtotime($_POST['sdate']);
			$where.="  and datetime > $sdate";
			$str = '&sdate='.$_POST['sdate'];
		}
		if($_POST['edate']){
			$edate = strtotime($_POST['edate']) + 24* 3600;
			$where.="  and datetime < $edate";
			$str = '&edate='.$_POST['edate'];
		}
			$param['records'] = $this->m->get($where." and pid = '$pid' order by datetime desc");
			$this->display('v/store/history',$param);    
  }

  function calculate()
  {
    global $app_id;
    $where = " and app_id='".$app_id."'";
    $str  = '';
    $ne = $param = array();

    $param['category'] = $category =  addslashes(isset($_POST['category'])?$_POST['category']:'');
    if($category){
      $where.="  and category = '$category'";
      $str.= '&category='.$category;
    }
    
    /*
    $param['customer'] = $customer =  addslashes($_POST['customer']?$_POST['customer']:'');
    if($customer){
      $where.="  and customer = '$customer'";
      $str.= '&customer='.$customer;
    }
     */
    $param['sdate'] = $sdate =  isset($_POST['sdate'])?$_POST['sdate']:date('Y-m-d');
    $param['edate'] = $edate = isset($_POST['edate'])?$_POST['edate']:date('Y-m-d');
    

    $sd = strtotime($sdate);
    $where.="  and datetime > $sd";
    $str.= '&sdate='.$sdate;

    $ed = strtotime($edate) + 24* 3600;
    $where.="  and datetime < $ed";
    $str.= '&edate='.$edate;
    
    $tot = 9000;//$this->m->count($where);
    $psize = 3000;
    //$pcurrent = isset( $_POST['p'] )? $_POST['p']:0;
    //$param['pagination'] = pagination($tot , $pcurrent , $psize ,'/stock/index/?p=');
    $rr =  $this->m->calculate($where);
    $eku = $this->m->eku();
    foreach($eku as $e){
      $pid = $e['pid'];
      $ne[$pid] = $e;
    }
    $param['eku'] = $ne;
    $param['records'] = $rr;
    $this->display('v/store/calculate',$param);    
  }
  
  
  function sys($action ='' , $aid ='' )
  {
    if( $this->app['admin'] != $this->u['id'] ) redirect(BASE.'store/','对不起，您不是管理员'); 
    if( $aid == $this->u['id'] ) redirect('?/store/','无权限');    

    switch($action){
      case 'remove':
        $ua = new user_app;
        $ua->delone($aid,$app_id);
        redirect('?/store/sys','删除成功');    
        break;
      case 'reset':
        load('m/user_m')->update($aid , array('password'=>''));
        redirect('?/store/sys','密码清空','系统将记录下次登录时输入密码为帐号密码！',10);
        break;
      default:
    }
    
    $param['app'] = $app = $this->app;
    $param['data'] = $data = is_array(json_decode($app['data'],true))?json_decode($app['data'],true):array();

    if (isset($_POST['title'])) load('m/app')->update( $app_id , array('title'=>$_POST['title']));

    if (isset($_POST['version']) || isset($_POST['module'])|| isset($_POST['users'])) {
      $data = array_merge($data,$_POST);
      $data = json_encode($data);
      load('m/app')->update( $app_id , array('data'=>$data));
      redirect(BASE.'store/sys','修改成功');
    }
    
    if ( isset($_POST['password']) || isset($_POST['password'])) {
      if(!load('m/user_m')->checkpwd($this->u['id'],$_POST['password']))redirect(BASE.'store/sys/','原密码错误');
      $this->m->truncate($app_id);
      redirect(BASE.'store/sys/','删除成功');
    }
    
    $param['users'] = $users = load('m/app')->users($app_id);
    
    if(sizeof($users)!=sizeof($data['users'])){
      foreach($users as $u){
        $uid = $u['id'];
        $uname = explode('@',$u['email']);
        if(!$data['users'][$uid]) $nu[$uid] = $uname[0];
        else $nu[$uid] = $data['users'][$uid];
      }
      $data['users'] = $nu;
      load('m/app')->update( $app_id , array('data'=>json_encode($data)));
    }
    $param['data'] = $data;
    $this->display('v/store/sys',$param);
  }
  
  function help()
  {
		$this->display('v/store/help',$param);    
  }
  
  function pid($pid)
  {
    $p = $this->m->getbypid($pid);
    echo json_encode($p);
  }
  
  function data()
  {
    $neku = array();
    $eku =  $this->m->eku();
    foreach($eku as $e){
      $eid = $e['pid'];
      $neku[$eid] = $e;
    }
   echo json_encode($neku);
  }
  
  function pname($pid)
  {
    $p = $this->m->getbypname($pid);
    echo json_encode($p);
  }
  
  function view($id)
  {
    $param['r'] = $this->m->get($id);
    $this->display('eku/show',$param);
  }

  function display($view,$param = array())
  {
    $param['al_content'] = view($view,$param,TRUE);
    $param['u'] = $this->u;
    $param['menu']  = $this->menu;
    header("Content-type: text/html; charset=utf-8");
    view('v/store/template',$param);
  }

  function export()
  {
    $param['records'] = $this->m->eku();
    $this->excel('v/store/stock_excel',$param);    
  }

  function export_history()
  {
    global $app_id;
    $where = " and app_id='".$app_id."'";
    $param['records'] = $this->m->get($where.' order by datetime desc' , 0, 9999999999999);
    $this->excel('v/eku/history_excel',$param);    
  }
  
  function edit($pid)
  {
    $pid = mysql_real_escape_string($pid);
    
    
    if ( $_POST['action'] =='del') {
      $this->m->update($_POST['id'],array('cur_balance' =>0));
      redirect(BASE.'store/stock/','修改成功！');
    }
    
    $conf = array('pid'=>'required','pname'=>'required','unit'=>'required');
    //,'num'=>'required','action'=>'required','action_label'=>'required','alert_level'=>'required','kuwei'=>'required','datetime'=>'required','doer'=>'required','remark'=>'required','category'=>'required','balance'=>'required','cur_balance'=>'required',);
    $err = validate($conf);
  	if ( $err === TRUE) {
      $this->m->update($_POST['id']);
      redirect(BASE.'store/stock/edit/'.$_POST['pid'].'/','修改成功！');
    }
    else {
      $r = $this->m->get(" and pid = '$pid' and cur_balance = 1 ");
      $param['val'] =  $r[0];
      $this->display('v/store/edit',$param); 
    }   
  }
  
  
  function account()
  {
    $id = $this->u['id']; 
    // update password 
    $conf = array('password'=>'required|comparetopwd','repassword'=>'required');
    $err = validate($conf);
    $_POST = array();
    if ( $err === TRUE) {
      if(!load('m/user_m')->checkpwd($id,$_POST['oldpassword']))redirect(BASE.'account/','原密码错误');
      $_POST['post_time'] = $_POST['update_time'] = time();
      load('m/user_m')->update_user($id);
      redirect('?/store/account/','修改成功');
    }
    else if ( isset($_POST['email']) || isset($_POST['username'])) {
      $_POST['post_time'] = $_POST['update_time'] = time();
      load('m/user_m')->update($id);
      redirect('?/store/account','修改成功');
    }
    else {
      $param['val'] = array_merge($_POST,load('m/user_m')->get($id)); 
      $param['err'] = $err;
      $this->display('v/store/account',$param);
    } 
  }

}

function comparetopwd()
{
  if($_POST['password'] == $_POST['repassword']) return true;
  return '两次输入的密码不一致';
}

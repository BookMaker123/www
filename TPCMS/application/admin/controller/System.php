<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\System as SystemModel;
use think\Db;
use think\facade\Request;

class System extends Base
{
	protected $system = 1; 
	public function index()
	{
		$list = SystemModel::where('id',$this->system)->find();
		if ($this->request->isPost()) {
			$data = $this->request->post('','null','trim');
			if(SystemModel::where('id',$this->system)->update($data)) return $this->success('配置成功！'); return $this->error('配置失败！');
		}
		$this->assign('list', $list);
		return $this->fetch();
	}



}
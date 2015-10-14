<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class SensitiveWordController extends BaseController
{
	public function indexAction(Request $request)
    {   

    	$sensitiveWordSetting = $this->getSettingService()->get("sensitiveWord", array());
    	if($request->getMethod() == 'POST'){
    		$fields = $request->request->all();
    		$sensitiveWordSetting = ArrayToolkit::parts($fields, array("ignoreWord", "wordReplace", "firstLevel", "secondLevel"));

            var_dump(explode("\ ", $sensitiveWordSetting["firstLevel"]));

    		$this->getSettingService()->set("sensitiveWord", $sensitiveWordSetting);

    	}

        return $this->render('TopxiaAdminBundle:SensitiveWord:index.html.twig', array(
        	"sensitiveWordSetting" => $sensitiveWordSetting
        ));
    }


    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }
}
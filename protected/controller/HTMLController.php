<?php
/**
 * Description of MainController
 *
 * @author stevelee
 */
class HTMLController extends DooController{

    public function usage(){
        $data['baseurl'] = Doo::conf()->APP_URL;
        $this->view()->renderc('usage', $data);
    }
    
    public function test(){
        $data['baseurl'] = Doo::conf()->APP_URL;
        $this->view()->renderc('test', $data);
    }
}
?>
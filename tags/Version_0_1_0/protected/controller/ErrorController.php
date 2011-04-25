<?php
/**
 * Description of ErrorController
 *
 * @author darkredz
 */
class ErrorController extends DooController {
    
    //put your code here
    function index(){
        $data['title'] = 'Error 404 not found	';
        $data['content'] = 'There is something wrong with the api query made.';
        $data['baseurl'] = Doo::conf()->APP_URL;
        $data['printr'] = null;
        $this->view()->renderc('template', $data);
    }
}
?>
<?php
class View
{
	private $view_path = 'view/';
	private $default_template = 'template';
	private $template;
	private $view;
	private $data;

	function __construct($view=null, $data=array()) {
		$this->template = $this->getViewPath($this->default_template);
		if( !is_null($view) ){
			$this->view = $this->getViewPath($view);
		}		
		$this->data = $data;
    }

    public static function make($view=null, $data=array()){
    	if( is_null($view) ){
    		return new View();
    	}
    	return new View($view, $data);
    }

    private function getViewPath($name){
    	$viewFilePath = $this->view_path.$name.'.php';
    	if(file_exists($viewFilePath)){
    		return $viewFilePath;
    	}else{
    		throw new Exception("Error: View({$name}) is not exist");    		
    	}
    }

    public function with($name, $data=null){
    	if( is_null($data) ){
    		if( !is_array($name) ){
    			$name = array($name);
    		}
    		$loadData = $name;    		
    	}else{
    		$loadData[$name] = $data;
    	}
    	$this->data = array_merge($this->data, $loadData);    	
    	return $this;
    }

    public function load($view){
    	$this->view = $this->getViewPath($view);
    	return $this;
    }

	private function load_view($page,$content_var=array(),$return=FALSE){
		extract($content_var);
		ob_start();
		include($page);
		if( $return ){
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}		
		@ob_end_flush();		
	}
	public function render($template=null){
		$content = $this->load_view($this->view, $this->data, TRUE);
		$masterData = array_merge($this->data, array('content'=>$content));
		if( !is_null($template) ){
			$this->template = $this->getViewPath($template);
		}
		return $this->load_view($this->template, $masterData, TRUE);
	}

	public function __toString(){
		return $this->render();
	}
}
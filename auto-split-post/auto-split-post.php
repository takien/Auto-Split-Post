<?php
/*
	Plugin Name: Auto Split Post
	Plugin URI: http://takien.com
	Description: Easily split post into multiple page with Title link, not just a number. It's better than default WordPress split functionality. Also support "AUTO SPLIT", for example you want split post on each &lt;h2&gt; tag. However you can still use &lt;!--nextpage--&gt; to manually split a post.
	Version: 0.1
	Author: takien
	Author URI: http://takien.com/
	Text Domain: split-post
*/

require_once(dirname(__FILE__).'/inc/plugin-bootstrap.php');

class AutoSplitPost extends PluginBootstrap {

	var $plugin_name    = 'Auto Split Post';
	var $plugin_slug    = 'auto-split-post';
	var $plugin_version = '0.1';
	var $plugin_use_option = true;
	
	/* initial setting/option */
	var $option_default = Array(
		'autosplit'                => 'yes',
		'split_at'                 => 'h2',
		'style'                    => 'linklist',
		'position'                 => 'aftercontent',
		'heading_text'             => 'Pages: ',
		'item_template'            => '%Page% %n% - %title%',
		'remove_default_pagination'=>'yes'
	);
	

	public function __construct($init=true) {
		if($init) {
			parent::__construct();
			
			$this->plugin_init();
			
		}
		$this->plugin_options(); 
	}
	
	function plugin_init() {
		$this->plugin_url = plugins_url('/', __FILE__);
		$this->plugin_dir = plugin_dir_path(__FILE__);
	}
	function plugin_filter() {
		return Array(
			'posts_results' => Array('split_post_pre_filter')
		);
	}
	function plugin_action() {
		return Array(
			'wp_head' => Array('auto_split_post_style_header')
		);
	}

	function plugin_script(){
		//wp_enqueue_script($this->plugin_slug.'_script',$this->plugin_url.'/assets/auto-split-post.js',array('jquery'),$this->plugin_version),true);
	}
	function plugin_style() {
		if(is_singular()) {
			wp_enqueue_style($this->plugin_slug.'_style',$this->plugin_url.'assets/auto-split-post.css','',$this->plugin_version);
		}
	}
	

	function plugin_options() {

		$this->option_group         = $this->plugin_slug.'_option';
		$this->option_menu_name     = $this->plugin_name;
		$this->option_menu_slug     = $this->plugin_slug;
		$this->option_menu_location = 'add_menu_page';
		$this->option_menu_position = 100;
		$this->option_capability    = 'activate_plugins';
		$this->option_icon_big      = $this->plugin_url.'assets/images/menu-icon-big.png';
		$this->option_icon_small    = $this->plugin_url.'assets/images/menu-icon-small.png';

		$fields	= Array(
					Array(
						'name'         => 'autosplit',
						'label'        => __('Auto Split'),
						'type'         => 'select',
						'description'  => __('Set auto split yes or no'),
						'value'        => $this->option('autosplit'),
						'values'       => Array(
							'yes'      => __('Yes'),
							'no'       => __('No')
							)
					),
					Array(
						'name'          => 'split_at',
						'label'         => __('Split at tag'),
						'type'          => 'select',
						'description'   => __('Select HTML tag you want to split at'),
						'value'         => $this->option('split_at'),
						'values'        => Array(
							'h2'=> __('h2'),
							'h3'=> __('h3')
							)
					),
					Array(
						'name'          => 'style',
						'label'         => __('Style'),
						'type'          => 'select',
						'description'   => __('Pagination style'),
						'value'         => $this->option('style'),
						'values'        => Array(
							'dropdown'=> __('Dropdown'),
							'linklist'=> __('Link list'),
							'tab'     => __('Tab')
							)
					),
					Array(
						'name'         => 'position',
						'label'        => __('Position'),
						'type'         => 'select',
						'description'  => __('Select pagination position'),
						'value'        => $this->option('position'),
						'values'       => Array(
							'beforecontent' => __('Before content'),
							'aftercontent' => __('After content'),
							'both'     => __('Both'),
							'manual'   => __('I will place it manually')
						)
					),
					Array(
						'name'          => 'heading_text',
						'label'         => __('Heading text'),
						'type'          => 'text',
						'description'   => __('Text displayed before link list'),
						'value'         => $this->option('heading_text')
					
					),
					Array(
						'name'          => 'item_template',
						'label'         => __('Item template'),
						'type'          => 'text',
						'description'   => __('%n% = page number; %page% = Page, translateable; %title% = Title; if any'),
						'value'         => $this->option('item_template')
						
					),
					Array(
						'name'         => 'remove_default_pagination',
						'label'        => __('Hide default pagination'),
						'type'         => 'select',
						'description'  => __('Some theme has it\'s own pagination for splitted post, eg: <code>twentyeleven</code> and <code>twentyten</code> theme. <br/> If you choose yes, it will be hidden by CSS (not actually removed). To manually remove, you should delete <code>wp_link_pages()</code> on your single theme (usually, after <code>the_content()</code>)'),
						'value'        => $this->option('remove_default_pagination'),
						'values'       => Array(
							'yes'      => __('Yes'),
							'no'       =>__('No')
							)
					),
					//wp_link_pages
		);
		
		$this->option_fields = $fields;	
	}
	

	/* here is contextual*/
	function split_post_pre_filter($raw_posts){
	
		if($this->option('autosplit') == 'yes') {
			$splitter = $this->option('split_at',$this->plugin_slug.'_option');
			
			foreach($raw_posts as $num => $raw_post){
				foreach($raw_post as $key => &$post){
					if('post_content' == $key) {
						$post = str_ireplace("<$splitter","<!--nextpage--><$splitter",$post);
						if(strpos($post,'<!--nextpage-->') == 0){
							$post = preg_replace('/<!--nextpage-->/i','',$post,1);
						}
						$raw_post->$key = $post;
					}
				}
				$raw_posts[$num] = $raw_post;
			}
		}
		
		return $raw_posts;
	}
	
	function auto_split_post_pagination(){
		$before      = '<div class="auto-split-post"><p class="auto-split-post-heading">'.$this->option('heading_text',$this->plugin_slug.'_option'). '</p><ul>';
		$after       = '</ul></div>';
		$link_before = '<li>';
		$link_after  = '</li>';
		//echo $this->option('style',$this->plugin_slug.'_option');
		if($this->option('style',$this->plugin_slug.'_option') == 'dropdown'){
			
			$before      = '<div class="auto-split-post"><p class="auto-split-post-heading">'.$this->option('heading_text',$this->plugin_slug.'_option'). '</p><select class="auto-split-post-dropdown">';
			$after       = '</select ></div>';
			$link_before = '<option>';
			$link_after  = '</option>';
		}
		$arg = Array(
			'echo'       => '',
			'before'     => $before,
			'after'      => $after,
			'link_before'=> $link_before,
			'link_after' => $link_after,
			'pagelink'   => '__%__'
		);
		$link = wp_link_pages($arg);
		
		/* if dropdown, remove anchor*/
		if($this->option('style',$this->plugin_slug.'_option') == 'dropdown'){
			$link = strip_tags($link,'<p><div><span><select><option>');
		}
		
		if($this->option('autosplit') == 'yes') {
			$return = preg_replace_callback('/__(\d)__/i',array(&$this,'auto_split_post_pagination_cb'), $link);
		}
		else {
			$return = $link;
		}
		echo $return;
	}
	
	function auto_split_post_pagination_cb($matches){
		$splitter    = $this->option('split_at',$this->plugin_slug.'_option');
		$template    = $this->option('item_template',$this->plugin_slug.'_option');
		$post        = get_post(get_the_ID());
		$all_content = $post->post_content;
		$splits      = array_filter(explode('<!--nextpage-->', $all_content));
		$i=0;
		$title = array();
		foreach($splits as $piece){ $i++;
			preg_match('/<'.$splitter.'[^>]*>(.*?)<\/'.$splitter.'>/i',$piece,$match);
			if(isset($match[1])){
				$text_title = $match[1];
				
				$title[$i] = str_replace('%Page%',__('Page'),$template);
				$title[$i] = str_replace('%page%',__('page'),$title[$i]);
				$title[$i] = str_replace('%n%',$i,$title[$i]);
				$title[$i] = str_ireplace('%title%',$text_title,$title[$i]);
			}
			else {
				$template_no_title  = str_ireplace('%title%','',$template);
				$title[$i] = str_replace('%Page%',__('Page'),$template_no_title);
				$title[$i] = str_replace('%page%',__('page'),$title[$i]);
				$title[$i] = str_replace('%n%',$i,$title[$i]);

			}
		}
		return $title[$matches[1]];
	}
	
	function auto_split_post_style_header(){
		if($this->option('remove_default_pagination',$this->plugin_slug.'_option') == 'yes') {
			echo '<style type="text/css">
				.page-link{
					display:none;
				}
			</style>';
		}
	}

}
new AutoSplitPost;

function auto_split_post_pagination() {
	$pagination = new AutoSplitPost(false);
	return $pagination->auto_split_post_pagination();
}

?>
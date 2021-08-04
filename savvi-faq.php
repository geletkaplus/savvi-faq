<?
/**
 * @package Savvi FAQ
 * @version 1.0
 */
/*
Plugin Name: Savvi FAQ
Plugin URI: 
Description: Description to come.
Author: Geletka+
Version: 1.0
Author URI: https://geletkaplus.com/
*/

class SavviFAQ{
	protected static $instance;
	const INIT = 'lKGCM5p5w3Jn';
	const OPTIONS_DB_LABEL = 'savvi_faq_options';
	const MAX_FAQ_COUNT = 12;
	private $settings;

	function __construct() {
		add_filter('plugin_action_links', [$this, 'action_links'], 10, 2);

		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

		add_action('wp_ajax_nopriv_savvi_faq_register_interaction', [$this, 'register_interaction']);
		add_action('wp_ajax_savvi_faq_register_interaction', [$this, 'register_interaction']);

		add_action('wp_ajax_nopriv_savvi_faq_save', [$this, 'savvi_faq_save']);
		add_action('wp_ajax_savvi_faq_save', [$this, 'savvi_faq_save']);

		add_action('wp_ajax_nopriv_savvi_faq_delete', [$this, 'savvi_faq_delete']);
		add_action('wp_ajax_savvi_faq_delete', [$this, 'savvi_faq_delete']);

		add_action('wp_ajax_nopriv_savvi_faq_options_save', [$this, 'savvi_faq_options_save']);
		add_action('wp_ajax_savvi_faq_options_save', [$this, 'savvi_faq_options_save']);

		add_action('admin_menu', [$this, 'settings']);
		
		add_shortcode('savvi_faq', [$this, 'render']);
		
		//get settings:
		$this->$settings = get_option(self::OPTIONS_DB_LABEL);
		
		$this->init();
	}

	public function init(){
	}

	public function action_links($links, $file) {
		if(current_user_can('manage_options')){
			$settings = '<a href="'. admin_url('options-general.php?page=savvi-faq%2Fsavvi-faq.php') .'">'. esc_html__('Settings', 'savvi-faq%2Fsavvi-faq.php') .'</a>';
			array_unshift($links, $settings);
		}
		return $links;
	}
	
	public function enqueue_scripts(){
		wp_enqueue_script('jquery');
		wp_enqueue_script('savvi-faq', plugins_url( 'js/savvi-faq.js', __FILE__ ), 10, 1);
		wp_enqueue_style('savvi-faq-style', plugins_url( 'css/savvi-faq.css', __FILE__ ), array(), _S_VERSION );
	}

	public function register_interaction(){
		$faq_id = $_POST['faq_id'];
		$faq_label = $_POST['faq_label'];
		$interaction = ($_POST['interaction'] == 'close') ? 'close' : 'open';
		$order = intval($_POST['order']);
		$event_id = $_POST['event_id'];
		
		$container_url = $this->$settings['container_url'];
		
		if(!$container_url){
			exit('{"result":-1, "message":"Missing client container URL"}');
		}
		
		if($faq_id && $interaction == 'open'){
			$ch = curl_init();

			$data = [
				'ClickEvent'=>1,
				'FAQPosition'=>$order,
				'decision_used'=>$faq_label,
				'decision_used_id'=>$faq_id,
				'stemeventid'=>$event_id,
			];

			curl_setopt($ch, CURLOPT_URL, 'https://8c80-efb5986285f2.cc.savvi-ai.com/stem/wp_faq_order');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$server_output = curl_exec($ch);

			curl_close ($ch);
			
			$new_get_request = $this->get_stem_event_id();
			
			$output = ['result'=>1, 'response'=>$server_output, 'stemeventid'=>$new_get_request->stemeventid];
			
			exit(json_encode($output));
		}

		exit('{"result":0}');
	}

	public function savvi_faq_save(){
		if(!current_user_can('edit_posts')){
			exit(0);
		}

		$question = $_POST['question'];
		$answer = $_POST['answer'];
		$faq_id = intval($_POST['faq_id']);
		
		if(!$faq_id){
			$current_faqs = SavviFAQ::get_faqs();
			if(count($current_faqs) >= self::MAX_FAQ_COUNT){
				exit(json_encode(['result'=>0, 'message'=>'The maximum number of FAQs is '.self::MAX_FAQ_COUNT.'.'], JSON_NUMERIC_CHECK));
			}
		}
	
		if(!$faq_id){
			$faq_id = wp_insert_post(array(
				'post_title' => $question,
				'post_type' => 'savvi_faq',
				'post_status' => 'private',
				'meta_input' => array(
					'question' => $question,
					'answer' => $answer,
					'interactions' => '',
				),
			));
		}else{
			wp_update_post(array(
				'ID'=>$faq_id,
				'post_title' => $question,
				'meta_input' => array(
					'question' => $question,
					'answer' => $answer,
				)
			));
		}
	
		exit(json_encode(['result'=>1, 'faq_id'=>$faq_id], JSON_NUMERIC_CHECK));
	}

	public function savvi_faq_delete(){
		if(!current_user_can('edit_posts')){
			exit(0);
		}

		$faq_id = intval($_POST['faq_id']);
		wp_delete_post($faq_id);
	
		exit(json_encode(['result'=>1], JSON_NUMERIC_CHECK));
	}
	
	public function savvi_faq_options_save(){
		$data = array(
			//'faq_collapsible' => (intval($_POST['faq_collapsible']) == 1) ? true : false,
			'container_url' => $_POST['container_url'],
		);
		if(update_option(self::OPTIONS_DB_LABEL, $data)){
			exit(json_encode(['result'=>1], JSON_NUMERIC_CHECK));
		}
		
		exit(json_encode(['result'=>0], JSON_NUMERIC_CHECK));
	}

	public function settings(){
		add_options_page('Savvi FAQ', 'Savvi FAQ', 'administrator', __FILE__, [$this, 'build_settings_page']);
	}

	public function build_settings_page() {
		wp_tiny_mce(true);
		
		$savviFAQListTable = new SavviFAQ_List_Table();
		$savviFAQListTable->prepare_items();
		
		$posts = SavviFAQ::get_faqs();
		
		//$settings_faq_collapsible = true;
		//if(isset($this->$settings['faq_collapsible']) && $this->$settings['faq_collapsible'] === false){
			//$settings_faq_collapsible = false;
		//}
		
		//we can properly enqueue this later:
		echo '<link rel="stylesheet" href="'.plugins_url( 'css/savvi-faq-settings.css', __FILE__ ).'">';
		?>
		<form id="form-<?=self::INIT?>">
			<h1>Savvi FAQ Settings</h1>
		
			<p>The Savvi FAQ plugin allows you add a collapsible list of FAQs to any part of your Wordpress site. However, to get the full benefits of this plugin you should have a Savvi AI account. [Some explanation of Savvi AI, using machine learning to order the FAQs in the most-clicked order, etc.] </p>
			
			<ul>
				<li>Add questions to the FAQs using the controls below. You can have up to 12 questions.</li>
				<li><a href="<?=plugins_url( 'files/WP_FAQ_Order.csv', __FILE__ )?>" download="WP_FAQ_Order.csv">Download this Savvi Stem template.</a></li>
				<li>Create a Savvi AI account.</li>
				<li>Create a new Stem in your Savvi AI account and upload the template. [Probably need some more directions from the Savvi team for this.]</li>
				<li>Input the newly created Stem's client container URL in the Settings at the bottom of this page and click the "Save" button.</li>
				<li>You're all set! You can embed the FAQs anywhere on your Wordpress site using the shortcode <code>[savvi_faq]</code>.</li>
			</ul>

			<h2>FAQs</h2>
			<? if(count($posts) < self::MAX_FAQ_COUNT){?>
			<p><input type="button" class="button" value="Add FAQ" data-savvi-faq-action="new-faq-modal"></p>
			<? }?>

			<div id="savvi-results-display"></div>

			<div id="savvi-faq"><? $savviFAQListTable->display();?></div>
		
			<h2>Settings</h2>
			<p>[Some explanation of Savvi AI and where to find the client container URL, etc.]</p>
			
			<div id="savvi-options-info"></div>
			
<!-- 
			<fieldset>
				<label for="savvi-faq-collapsible">
					<input name="savvi-faq-collapsible" type="checkbox" id="savvi-faq-collapsible" value="1"<?=($settings_faq_collapsible)?' checked':''?>> Display FAQ as collapsible pods
				</label>
			</fieldset>
 -->
			<fieldset>
				<label for="savvi-faq-container-url">Savvi Stem client container URL:</label>
				<input name="savvi-faq-container-url" type="text" id="savvi-faq-container-url" value="<?=$this->$settings['container_url']?>" class="savvi-faq-text-input-wide">
			</fieldset>
		
			<p><input type="button" class="button button-primary" value="Save Options" data-savvi-faq-action="save"></p>
		
			<div class="savvi-faq-overlay">
				<div class="savvi-faq-modal">
					<input type="hidden" name="faq_id" value="0">
					<h2>Create/Edit FAQ</h2>
					<label>Question</label>
					<input type="text" name="question" class="savvi-faq-text-input">
					<a href="#" class="savvi-faq-toggle-view" data-savvi-faq-action="toggle-view">Toggle View</a>
					<label>Answer</label>
					<textarea id="savvi-faq-textarea"></textarea>
					<p><input type="button" class="button button-primary" value="Add FAQ" data-savvi-faq-action="add-faq"></p>
					<div class="savvi-faq-close"></div>
				</div>
			</div>
		
			<div class="savvi-faq-loading"></div>
		</form>
		<script>
		var savvi_faqs = <?=json_encode($posts, JSON_NUMERIC_CHECK)?>;
		
		</script>
		<?
		//we can properly enqueue this later:
		echo '<script src="'.plugins_url( 'js/savvi-faq-settings.js', __FILE__ ).'"></script>';
	}

	public function render($atts){
		$unordered_questions = SavviFAQ::get_faqs();
		$questions = [];

		$container_url = $this->$settings['container_url'];
		
		if($container_url){
			$server_output = $this->get_stem_event_id();
		}

		if($container_url && $server_output->decision){
			foreach($server_output->decision as $k=>$v){
				//unordered questions come in as a zero-based array; match them up to returned faq ID, which is 1-based:
				$adjust_id = intval($v->id) - 1;
				
				if($unordered_questions[$adjust_id]){
					$question_data = $unordered_questions[$adjust_id];
					$question_data['label'] = $v->label;
					$question_data['id'] = $v->id;
					$questions[] = $question_data;
				}
			}
		}else{
			$questions = $unordered_questions;
		}

		echo '<div data-savvi-faq-event-id="'.$server_output->stemeventid.'" data-savvi-faq-uid="savv-faq-'.uniqid().'">';
		foreach($questions as $k=>$v){?>
		<div class="savvi-faq" id="savvi-faq-<?=$v['faq_id']?>" data-savvi-faq-id="<?=$v['id']?>" data-savvi-faq-label="<?=htmlspecialchars($v['label'], ENT_QUOTES)?>" data-savvi-faq-order="<?=$k?>">
			<div class="savvi-click-area savvi-collapsed" data-savvi-faq-toggle="collapse" data-savvi-faq-target="#faq-answer-<?=$k?>" aria-expanded="false" aria-controls="savvi-faq-<?=$k?>"></div>
			<div class="savvi-bg"></div>
			<div class="savvi-question"><?=$v['question']?></div>
			<div class="savvi-answer savvi-collapse" id="savvi-faq-answer-<?=$k?>">
				<?=$v['answer']?>
			</div>
			<div class="savvi-toggle"></div>
		</div>
		<? }
		echo '</div>';

		return;
	}
	
	/* STATIC METHODS */
	/* remember, static funcs accessed thusly: SavviFAQ::get_faqs(); */
	public static function get_faqs(){
		$posts = get_posts([
			'numberposts'=> -1,
			'post_type' => 'savvi_faq',
			'post_status' => 'private',
			'order' => 'ASC',
		]);
		
		$output = [];
		foreach($posts as $k=>$v){
			$output[] = [
				'faq_id'=> $v->ID,
				'question'=> get_post_meta($v->ID, 'question', true),
				'answer'=> get_post_meta($v->ID, 'answer', true),
				'interactions'=> get_post_meta($v->ID, 'interactions', true),
			];
		}
		
		return $output;
	}
	
	/* PRIVATE METHODS */

	private function get_stem_event_id(){
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		$os_platform = 'unknown platform';
		$os_array = array(
			'/iphone/i' => 'iPhone',
			'/ipod/i' => 'iPod',
			'/ipad/i' => 'iPad',
			'/windows/i' => 'Windows',
			'/win98/i' => 'Windows',
			'/win95/i' => 'Windows',
			'/win16/i' => 'Windows',
			'/macintosh|mac os x/i' => 'MacOS',
			'/linux/i' => 'Linux',
			'/ubuntu/i' => 'Ubuntu',
			'/android/i' => 'Android',
			'/blackberry/i' => 'BlackBerry',
			'/webos/i' => 'Mobile'
		);

		foreach($os_array as $regex => $value){ 
			if(preg_match($regex, $user_agent)){
				$os_platform = $value;
				break;
			}
		}

		$browser = 'unknown browser';
		$browser_array = array(
			'/firefox/i' => 'Firefox',
			'/edg/i' => 'Edge',
			'/chrome/i' => 'Chrome',
			'/safari/i' => 'Safari',
			'/opera/i' => 'Opera',
			'/netscape/i' => 'Netscape',
			'/maxthon/i' => 'Maxthon',
			'/konqueror/i' => 'Konqueror',
			'/msie/i' => 'Internet Explorer',
			'/mobile/i' => 'Handheld Browser'
		);

		foreach($browser_array as $regex => $value){
			if( preg_match($regex, $user_agent)){
				$browser = $value;
				break;
			}
		}

		$data = http_build_query([
			'DeviceType'=>$os_platform.' '.$browser,
			'ReferringURL'=>($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no referrer',
		]);

		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, 'https://9c08-56463ccdeb33.cc.savvi-ai.com/stem/wp_faq_order?'.$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return json_decode(curl_exec($ch));
	}

	//singleton enforcer; if instance exists, return it, otherwise instantiate:
	public static function getInstance() {
		if(!self::$instance){
			self::$instance = new self(); 
		}
		return self::$instance;
	}
}

require plugin_dir_path( __FILE__ ) . 'includes/class-savvi-faq-table-list.php';

$savvi_faq = new SavviFAQ();
?>

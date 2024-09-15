<?php

class GMNFC_Hello {
	private $logged_in;
	private $proceed;

	public function __construct($logged_in, $proceed) {
		$this->logged_in = $logged_in;
		$this->proceed = $proceed;
	}

	public function display_menu_page() {
		if ($this->logged_in) {
			return $this->main_menu_page_user();
		} else if( $this->proceed ) {
			wp_redirect( wp_login_url(home_url()) );
			exit;
		}else{
			return $this->main_menu_page_guest();
		}
	}

	private function main_menu_page_user() {
		$plugin_url = GMNFC_PLUGIN_URL;

		$answered = $this->getUserAnswered();

		$listHTML = "";

		foreach($answered as $post_id){
			$post = get_post($post_id);
			$listHTML .= "<div><a href='" . get_permalink($post_id) . "'>" . $post->post_title . "</a></div><br>";
		}

		if(empty($listHTML)){
			$listHTML = "<i>Nobena naloga še ni rešena. <br>Prva te čaka pri vhodu šole.</i>";
		}
		else {
			$stNalog = count($answered);
			$listHTML = "<p style='font-size: 0.8em; color: white;'>Stisni na rešene naloge, da si ponovno <br>ogledaš navodila za iskanje naslednje!</p>" . $listHTML . "<u style='text-decoration-color: white;'><p style='color: white;'>Število rešenih nalog: $stNalog</p></u><br>";
		}

		return <<<HTML
<link rel="stylesheet" href="$plugin_url/assets/page-resources/account-page.css">
		<div class="gmnfc-hello">
			
			<div class="gmnfc-header">
			<img src="$plugin_url/assets/logo.png" alt="logo">
			<h2>Kulturni maraton II. Gimnazije</h2>
    		</div>
    <div class="gmnfc-main">
        <p id="intro-text">
        	Pozdravljen/a, <br>
			Tvoje opravljene naloge so:<br><br>
				$listHTML
</p>
    </div>
    <div class="gmnfc-footer">
        <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
    </div>
</div>
<a class="gmnfc-logout" href="?logout=true">Odjava</a>
HTML;
	}

	public function main_menu_page_guest() {
		$plugin_url = GMNFC_PLUGIN_URL;
		return <<<HTML
<link rel="stylesheet" href="$plugin_url/assets/page-resources/account-page.css">
		<div class="gmnfc-hello">
			<div class="gmnfc-header">
			<img src="$plugin_url/assets/logo.png" alt="logo">
			<h2>Kulturni maraton II. Gimnazije</h2>
    		</div>
    <div class="gmnfc-main">
        <p id="intro-text">Pozdravljen/a na Lovu na zaklad!<br><br>
            Ta stran te bo vodila po šoli.<br>
            Ko boš prispel/a do prave lokacije boš dobil/a namige in izzive,<br>
            ki jih boš moral/a opraviti, če želiš napredovati.<br><br>
            Pri vsaki novi lokaciji boš moral/a skenirati skrito QR kodo<br>

            in tako boš napredoval/a k naslednji lokaciji.<br>
            <b><u>Prva lokacija je pri vhodu šole.</u></b><br><br>
            Na koncu te čaka nagrada!<br><br>
            
            Začneš tako, da stisneš gumb <b>"Nadaljuj"</b>, <br>
            ter se prijaviš s svojim šolskim Google računom<br>
            z klikom na gumb <b>"Login with Google"</b>.<br><br>

            V primeru napake ali tehničnega vprašanja se oglasi na info točki.</p>
        <a href="?lov=true" id="start-hunt">Nadaljuj</a>
    </div>
    <div class="gmnfc-footer">
        <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
    </div>
</div>
HTML;

	}

	private function getUserAnswered(){
		global $wpdb;

		$table_name = $wpdb->prefix . 'NFC_Hunt_Dijak';
		$table_name2 = $wpdb->prefix . 'NFC_Hunt_Naloga';
		$table_name3 = $wpdb->prefix . 'NFC_Hunt_Odgovori';

		$user_id = get_current_user_id();

		$user = $wpdb->get_results("SELECT ID_DIJAK FROM $table_name WHERE FK_UserID = $user_id");
		$user = $user[0];
		$dijak_id = $user->ID_DIJAK;

		$answeredNalogas = $wpdb->get_results("SELECT FK_NalogaID FROM $table_name3 WHERE FK_DijakID = $dijak_id");
		$answeredNalogas = array_map(function($nalogas){
			return $nalogas->FK_NalogaID;
		}, $answeredNalogas);

		if (empty($answeredNalogas)) return array();

		// get all post ids from these nalogas
		$posts = $wpdb->get_results("SELECT FK_PostID FROM $table_name2 WHERE ID_NALOGA IN (" . implode(",", $answeredNalogas) . ")");

		$posts = array_map(function($post){
			return $post->FK_PostID;
		}, $posts);

		return $posts;
	}

}
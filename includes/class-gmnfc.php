<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GMNFC {
    public function __construct(){
        // add_action('user_register', array($this, 'proccessUser'), 10, 1);
        // add_action('admin_menu', array($this, 'addAdminMenu'));
    }

    public function proccessUser($user_id){
        // insert data into the database
        global $wpdb;

        $table_name = $wpdb->prefix . "NFC_Hunt_Dijak";

        $wpdb->insert(
            $table_name,
            array(
                'Tocke' => 0,
                'Cas_Registracije' => current_time('mysql'),
                'FK_UserID' => $user_id
            )
        );

    }

    public function addNaloga($vprasanje, $tip, $odgovor, $tocke, $reqprev, $correctmsg){

		$permalink_code = "";

        if( current_user_can('administrator') ) {
            global $wpdb;

            $table_name = $wpdb->prefix . "NFC_Hunt_Naloga";

			$permalink_code = substr(sha1($vprasanje . time()), 0, 15);

            $wpdb->insert(
                $table_name,
                array(
                    'Vprasanje' => $vprasanje,
                    'Tip' => $tip,
                    'Odgovor' => $odgovor,
                    'Tocke' => $tocke,
	                'Permalink' => $permalink_code
                )
            );
        }

	    $results = $wpdb->get_results("SELECT ID_NALOGA FROM $table_name WHERE Permalink = '$permalink_code'");
		$perma_id = $results[0]->ID_NALOGA;

		// create a new post with the question as the title and the answer as a custom field
	    $post_id = wp_insert_post(array(
			'post_title' => $vprasanje,
			'post_type' => 'gmnfc_quiz',
			'post_status' => 'publish',
			'post_name' => $permalink_code,
			'post_content' => "[gmnfc-quiz id=$perma_id]" ,
			'meta_input'   => array(
				'odgovor' => $odgovor,
				'tocke' => $tocke,
				'tip' => $tip,
				'odgovorili' => '',
				'perma_id' => $perma_id,
				'permalink' => $permalink_code,
				'correctmsg' => $correctmsg,
				'reqprev' => $reqprev
			)
		));

		// add the post id to the database
	    $wpdb->update(
			$table_name,
			array(
				'FK_PostID' => $post_id
			),
			array(
				'ID_NALOGA' => $perma_id
			)
		);

		$post_permalink = get_permalink($post_id);
	    echo '<p style="font-size: 24px; color: green;">Naloga dodana!</p>';
	    echo '<p style="font-size: 12px; color: cornflowerblue;">Permalink: <a href=' . $post_permalink . '>' . $post_permalink .'</a></p>';
		echo $perma_id;
    }

    public function deleteNaloga($id){
        if( current_user_can('administrator') ) {
            global $wpdb;

            $table_name = $wpdb->prefix . "NFC_Hunt_Naloga";

			// get the post id
	        $post_id = $wpdb->get_results("SELECT FK_PostID FROM $table_name WHERE ID_NALOGA = $id");
			$post_id = $post_id[0]->FK_PostID;


            $wpdb->delete(
                $table_name,
                array(
                    'ID_NALOGA' => $id
                )
            );

			wp_trash_post($post_id);
        }
        echo '<p style="font-size: 24px; color: green;">Naloga izbrisana!</p>';
    }

    // function runs to add an admin menu
    public function addAdminMenu(){
        add_menu_page(
            'II. NFC Hunt',
            'II. NFC Hunt',
            'manage_options',
            'ii-nfc-hunt',
            array($this, 'adminMenuPage'),
            'dashicons-admin-users'

        );

        add_submenu_page(
            'ii-nfc-hunt',
            'II. NFC Hunt - Dijaki',
            'Dijaki',
            'manage_options',
            'ii-nfc-hunt-dijaki',
            array($this, 'adminMenuPageDijaki')
        );

        add_submenu_page(
            'ii-nfc-hunt',
            'II. NFC Hunt - Naloge',
            'Naloge',
            'manage_options',
            'ii-nfc-hunt-naloge',
            array($this, 'adminMenuPageNaloge')
        );

        add_submenu_page(
            'ii-nfc-hunt',
            'II. NFC Hunt - Nastavitve',
            'Nastavitve',
            'manage_options',
            'ii-nfc-hunt-nastavitve',
            array($this, 'adminMenuPageNastavitve')
        );

        add_submenu_page(
            'ii-nfc-hunt',
            'II. NFC Hunt - Pomoč',
            'Pomoč',
            'manage_options',
            'ii-nfc-hunt-pomoc',
            array($this, 'adminMenuPagePomoc')
        );

	    add_submenu_page(
		    'ii-nfc-hunt',
		    'II. NFC Hunt - Database',
		    'Database',
		    'manage_options',
		    'ii-nfc-hunt-db',
		    array($this, 'adminMenuPageDB')
	    );
    }

    public function adminMenuPage(){
        echo '<h1>II. NFC Hunt</h1>';

        // stats
        global $wpdb;

        $table_name = $wpdb->prefix . "NFC_Hunt_Dijak";

        $results = $wpdb->get_results("SELECT * FROM $table_name");


        echo '<p>Stevilo registriranih dijakov: ' . count($results) . '</p>';
        echo '<p>Stevilo nalog: ' . $this->getNalogeCount() . '</p>';
		echo '<p>Stevilo odgovorov: ' . $this->getOdgovoriCount() . '</p>';


    }

    public function adminMenuPageDijaki(){
        echo '<h1>II. NFC Hunt - Dijaki</h1>';

        // leaderboard
        global $wpdb;

        $table_name = $wpdb->prefix . "NFC_Hunt_Dijak";

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY Tocke DESC");

        echo '<h2>Leaderboard</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Ime</th>';
        echo '<th>Tocke</th>';
        echo '</tr>';

        foreach($results as $result){
            $user = get_userdata($result->FK_UserID);
            echo '<tr>';
			if($user->first_name == "" && $user->last_name == ""){
				echo '<td>' . $user->user_login . '</td>';
			}else{
				echo '<td>' . $user->first_name . ' ' . $user->last_name . '</td>';
			}
            echo '<td style="text-align: end">' . $result->Tocke . '</td>';
            echo '</tr>';
        }

        echo '</table>';

    }

    public function adminMenuPageNaloge(){
        echo '<h1>II. NFC Hunt - Naloge</h1>';

        // naloge
        global $wpdb;

        $table_name = $wpdb->prefix . "NFC_Hunt_Naloga";

        if(isset($_POST['vprasanje'])){
            $this->addNaloga($_POST['vprasanje'], $_POST['tip'], $_POST['odgovor'], $_POST['tocke'], $_POST['reqprev'], $_POST['correctmsg']);
        }

        if(isset($_GET['delete'])){
            $this->deleteNaloga($_GET['delete']);
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name");

        echo '<h2>Naloge</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Vprašanje</th>';
        echo '<th>Tip</th>';
        echo '<th>Odgovor</th>';
        echo '<th>Tocke</th>';
        echo '</tr>';

        foreach($results as $result){
            echo '<tr>';
            echo '<td>' . $result->Vprasanje . '</td>';
            echo '<td>' . $result->Tip . '</td>';
            echo '<td style="text-align: center">' . $result->Odgovor . '</td>';
            echo '<td style="text-align: center">' . $result->Tocke . '</td>';
            echo '<td><a class="button-link-delete" href="?page=ii-nfc-hunt-naloge&delete=' . $result->ID_NALOGA . '">Izbriši</a></td>';
			// TODO: Add an edit button for each question just like the delete one
			echo '</tr>';

        }

        echo '</table>';

        // Form for adding a new task
        /*echo '<h2>Dodaj nalogo</h2>';
        echo '<form method="post">';
        echo '<label for="vprasanje">Vprašanje</label>';
        echo '<input type="text" name="vprasanje" id="vprasanje" required><br>';
        echo '<label for="tip">Tip</label>';
        echo '<select name="tip" id="tip" required><br>';
        echo '<option value="Text">Text</option>';
        echo '<option value="Radio">Radio</option>';
        echo '<option value="Number">Number</option>';
        echo '<option value="Checkbox">Checkbox</option>';
        echo '<option value="Slider">Slider</option>';
        echo '</select><br>';
        echo '<label for="odgovor">Odgovor</label>';
        echo '<input type="text" name="odgovor" id="odgovor" required><br>';
        echo '<label for="tocke">Tocke</label>';
        echo '<input type="number" name="tocke" id="tocke" required><br>';
        echo '<input type="submit" value="Dodaj nalogo">';
        echo '</form>';*/

	    // Get all the posts from the custom post type and store all their titles and ids in an array
		$args = array(
			'post_type' => 'gmnfc_quiz',
			'posts_per_page' => -1
		);

		$posts = get_posts($args);
		$posts_array = array();

		foreach($posts as $post){
			$posts_array[$post->ID] = $post->post_title;
		}

		$optionHTML = "";

		foreach ($posts_array as $key => $value){
			$optionHTML .= '<option value="' . $key . '">' . $value . '</option>';
		}

	    echo '<h2>Dodaj nalogo</h2>';
	    echo '<form method="post">';
	    echo '<table>';
	    echo '<tr><td><label for="vprasanje">Vprašanje</label></td><td><input type="text" name="vprasanje" id="vprasanje" required></td></tr>';
	    echo '<tr><td><label for="tip">Tip</label></td><td><select name="tip" id="tip" required>';
	    echo '<option value="Text">Text</option>';
	    echo '<option value="Upload">Upload</option><option value="Scan">Scan</option></select></td></tr>';
	    echo '<tr><td><label for="odgovor">Odgovor</label></td><td><input type="text" name="odgovor" id="odgovor" required></td></tr>';
	    echo '<tr><td><label for="tocke">Tocke</label></td><td><input type="number" name="tocke" id="tocke" required></td></tr>';
		echo '<tr><td><label for="reqprev">Requirement</label></td><td><select name="reqprev" id="reqprev"><option value="none"> - None - </option>' . $optionHTML . '</select></td></tr>';
	    echo '<tr><td><label for="correctmsg">Success Message</label></td><td><input type="text" name="correctmsg" id="correctmsg"></td></tr>';
		echo '</table>';
	    echo '<input type="submit" class="button" value="Dodaj nalogo">';
	    echo '</form>';


	    /*
		  if(isset($_GET['status'])){
			$this->deleteNaloga($_GET['status']);
			if ($_GET['status'] == 'deletion-success'){
				echo '<p style="font-size: 24px; color: green;">Naloga izbrisana!</p>';
			} else if ($_GET['status'] == 'deletion-failure') {
				echo '<p style="font-size: 24px; color: red;">Napaka pri brisanju naloge!</p>';
			}
			else if ($_GET['status'] == 'addition-success') {
				echo '<p style="font-size: 24px; color: green;">Naloga dodana!</p>';
			}
			else if ($_GET['status'] == 'addition-failure') {
				echo '<p style="font-size: 24px; color: red;">Napaka pri dodajanju naloge!</p>';
			}
		}*/
    }

    public function adminMenuPagePomoc(){
        echo '<h1>II. NFC Hunt</h1>';
        echo '<p>Plugin trenutno namenjen za "Kulturni maraton". Omogoča registracijo uporabnikov, beleženje njihove udeležbe na dogodku in točkovanje kvizov.</p>';
        echo '<p>Verzija: ' . GMNFC_PLUGIN_VERSION . '</p>';
        echo '<p>Avtor: Jan-Fcloud</p>';
        echo '<p><a href="https://jans.dev" target="_blank">https://jans.dev</a></p>';
        echo '<p>Text Domain: jans-gmnfc</p>';
		echo '<p>Plugin path: ' . GMNFC_PLUGIN_PATH . '</p>';
		echo '<p>Plugin URL: ' . GMNFC_PLUGIN_URL . '</p>';
		echo '<p>Site URL: ' . GMNFC_SITE_URL . '</p>';
		echo '<a class="button-secondary" href="edit.php?post_type=gmnfc_quiz"> Post Type Page</a>';
		echo '<a class="button-cancel" href="?page=ii-nfc-hunt-pomoc&rebuild=true">Rebuild DB</a><br>';
	    echo '<a class="button-cancel" href="?page=ii-nfc-hunt-pomoc&testusers=true">Add test users to db</a><br>';
		echo '<a class="button-cancel" href="?page=ii-nfc-hunt-pomoc&noanswers=true">Empty answer DB</a>';
		// TODO: Add a button which adds all the questions that I have been provided with


	    if(isset($_GET['rebuild'])){
		    $this->debug_rebuildDB($_GET['rebuild']);

			echo '<h2>Database has been rebuilt</h2>';
	    }

	    if(isset($_GET['testusers'])){
		    echo $this->debug_testUsers($_GET['testusers']);
	    }

		if(isset($_GET['noanswers'])){
			$this->debug_emptyAnswerDB();
			echo '<h2>Answer database has been emptied</h2>';
		}
    }

	public function adminMenuPageDB(){
		echo '<h1>II. NFC Hunt - Database</h1>';

		global $wpdb;

		$table_name = $wpdb->prefix . "NFC_Hunt_Dijak";
		$table_name2 = $wpdb->prefix . "NFC_Hunt_Naloga";
		$table_name3 = $wpdb->prefix . "NFC_Hunt_Odgovori";

		$results = $wpdb->get_results("SELECT * FROM $table_name");
		$results2 = $wpdb->get_results("SELECT * FROM $table_name2");
		$results3 = $wpdb->get_results("SELECT * FROM $table_name3");

		// style the tables to have outlines and are properly spaced out
		echo '<style>';
		echo 'table {border-collapse: collapse; width: 100%;}';
		echo 'th, td {border: 1px solid black; padding: 8px;}';
		echo 'th {background-color: #f2f2f2;}';
		echo '</style>';

		echo '<h2>Table: ' . $table_name . '</h2>';
		echo '<table>';
		echo '<tr>';
		echo '<th>ID_DIJAK</th>';
		echo '<th>Tocke</th>';
		echo '<th>Cas_Registracije</th>';
		echo '<th>FK_UserID</th>';
		echo '</tr>';

		foreach($results as $result){
			echo '<tr>';
			echo '<td>' . $result->ID_DIJAK . '</td>';
			echo '<td>' . $result->Tocke . '</td>';
			echo '<td>' . $result->Cas_Registracije . '</td>';
			echo '<td>' . $result->FK_UserID . '</td>';
			echo '</tr>';
		}

		echo '</table>';

		echo '<h2>Table: ' . $table_name2 . '</h2>';
		echo '<table>';
		echo '<tr>';
		echo '<th>ID_NALOGA</th>';
		echo '<th>Vprasanje</th>';
		echo '<th>Tip</th>';
		echo '<th>Odgovor</th>';
		echo '<th>Tocke</th>';
		echo '<th>Permalink</th>';
		echo '<th>FK_PostID</th>';
		echo '</tr>';

		foreach($results2 as $result){
			echo '<tr>';
			echo '<td>' . $result->ID_NALOGA . '</td>';
			echo '<td>' . $result->Vprasanje . '</td>';
			echo '<td>' . $result->Tip . '</td>';
			echo '<td>' . $result->Odgovor . '</td>';
			echo '<td>' . $result->Tocke . '</td>';
			echo '<td>' . $result->Permalink . '</td>';
			echo '<td>' . $result->FK_PostID . '</td>';
		}

		echo '</table>';

		echo '<h2>Table: ' . $table_name3 . '</h2>';
		echo '<table>';
		echo '<tr>';
		echo '<th>ID_ODGOVOR</th>';
		echo '<th>FK_DijakID</th>';
		echo '<th>FK_NalogaID</th>';
		echo '<th>Odgovor</th>';
		echo '<th>Pravilno</th>';
		echo '</tr>';

		foreach($results3 as $result){
			echo '<tr>';
			echo '<td>' . $result->ID_ODGOVOR . '</td>';
			echo '<td>' . $result->FK_DijakID . '</td>';
			echo '<td>' . $result->FK_NalogaID . '</td>';
			echo '<td>' . $result->Odgovor . '</td>';
			echo '<td>' . $result->Pravilno . '</td>';
		}

		echo '</table>';
	}

    public function adminMenuPageNastavitve(){
        echo '<h1>II. NFC Hunt - Nastavitve</h1>';
    }

    public function getNalogeCount() : int
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "NFC_Hunt_Naloga";

        $results = $wpdb->get_results("SELECT * FROM $table_name");

        return count($results);
    }

	public function getOdgovoriCount() : int {
		global $wpdb;

		$table_name = $wpdb->prefix . "NFC_Hunt_Odgovori";

		$results = $wpdb->get_results("SELECT * FROM $table_name");

		return count($results);
	}

    public function gmnfcQuizShortcodeLoad($id){

		if(!is_user_logged_in()){
			return $this->potrebnaPrijava();
		}

        global $wpdb;
		$post_answer_true = true;

		// check if the user sent an answer via post
		if(isset($_POST['answer'])){
			$answer = $_POST['answer'];
			$user_id = get_current_user_id();
			$question_id = $id;

			// submit the answer
			require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-forms.php';
			$form = new GMNFC_Forms();
			$result = $form->submitAnswer($user_id, $question_id, $answer);
			if( ! $result ){
				$post_answer_true = false;
			}
		}


	    if(isset($_FILES['file_upload'])){
			$answer = $_FILES['file_upload']['name'];
		    // check if the file is a video
		    // .mp4, .hevc, .webm, .avi, .mov, .flv, .mkv, .wmv
		    $video_extensions = array("mp4", "hevc", "webm", "avi", "mov", "flv", "mkv", "wmv", "mp3", "wav", "flac", "ogg", "m4a");
		    $ext = pathinfo($answer, PATHINFO_EXTENSION);

			// echo to see if there is anything in files
		     //return 'yeepeee';

		    //if(in_array($ext, $video_extensions)){
			    $user_id = get_current_user_id();
			    $question_id = $id;
			    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-forms.php';
			    $form = new GMNFC_Forms();
			    $result = $form->handleUpload($user_id, $question_id);
		    //}

	    }

		/*if (isset($result)){
			if($result == false){
				// return '<h1 style="color: red;">Napaka pri pošiljanju odgovora!</h1>';
			}
			else if($result == true){
				return '<h1 style="color: green;">Odgovor poslan!</h1>';
			}
		}*/

        $table_name = $wpdb->prefix . "NFC_Hunt_Naloga";

        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE ID_NALOGA = $id");

        if(count($results) == 0){
            return $this->errorPage();
        }

        $vprasanje = $results[0]->Vprasanje;
        $setID = "question" . $results[0]->ID_NALOGA;

		// check if the user has already answered the question which is required to be answered to access this one
	    $user_id = get_current_user_id();
	    $user_table = $wpdb->prefix . "NFC_Hunt_Dijak";
	    $post_answer_meta = get_post_meta(get_the_ID(), 'reqprev', true); // get the post id which needs to be answered first
		$no_requriement = false;

		if($post_answer_meta == "none"){
			$no_requriement = true;
		}

	    $post_answer_meta = get_post_meta($post_answer_meta, 'odgovorili', true); // get the users who have answered the question (post id
	    $odgovorili = explode(";", $post_answer_meta);

		$user = $wpdb->get_results("SELECT ID_DIJAK FROM $user_table WHERE FK_UserID = $user_id");
		$user_id = $user[0]->ID_DIJAK;

		// check if user already answered the question, if yes, we display a custom message
		$post_answer_meta = get_post_meta(get_the_ID(), 'odgovorili', true);
		$odgovorili_this = explode(";", $post_answer_meta);

	    if(in_array($user_id, $odgovorili_this)){
		    $succesmsg = get_post_meta(get_the_ID(), 'correctmsg', true);
		    return $this->alreadyAnswered($succesmsg);
	    }

		if(!in_array($user_id, $odgovorili) && $no_requriement == false){
			return $this->skipPrevented();
		}

	    // check if tip = Scan
	    if($results[0]->Tip == "Scan"){
		    $user_id = get_current_user_id();
		    $question_id = $id;

		    // submit the answer
		    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-forms.php';
		    $form = new GMNFC_Forms();
		    $result = $form->scanSubmit($user_id, $question_id);

		    $succesmsg = get_post_meta(get_the_ID(), 'correctmsg', true);
		    return $this->alreadyAnswered($succesmsg);
	    }

		$inputs = $this->handleInputs($results);
		$plugin_url = GMNFC_PLUGIN_URL;
		$question = $results[0]->Vprasanje;
		$post_notice = "";

		if( ! $post_answer_true ){
			$post_notice = "Poslan odgovor ni pravilen. Poskusite znova.";
		}

        return <<<HTML
		<link rel="stylesheet" href="$plugin_url/assets/page-resources/form-style.css">
		<div class="gmnfc-quiz">
   <div class="gmnfc-header">
      <img src="$plugin_url/assets/logo.png" alt="logo">
      <h2>Kulturni maraton II. Gimnazije</h2>
   </div>
   <div class="gmnfc-main">
      <form method="post" class="gmnfc-form" enctype="multipart/form-data">
         <label class="gmnfc-q-label" for="question3">$question</label><br>
         $inputs
         <br>
         
         <div class="submit-wrapper">
            <input type="submit" name="gmnfc-submit" value="Pošlji">
         </div>
         
         <div class="post-notice"><p>$post_notice</p></div>
      </form>
   </div>
   <div class="gmnfc-footer">
      <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
   </div>
</div>
HTML;
    }

    public function errorPage(){
	    $plugin_url = GMNFC_PLUGIN_URL;
        return <<<HTML
		<link rel="stylesheet" href="$plugin_url/assets/page-resources/form-style.css">
		<div class="gmnfc-quiz">
   <div class="gmnfc-header">
      <img src="$plugin_url/assets/logo.png" alt="logo">
      <h2>Kulturni maraton II. Gimnazije</h2>
   </div>
   <div class="gmnfc-main">
      <h2>Stran trenutno ni dosegljiva. Prosimo preverite povezavo in poskusite znova čez nekaj trenutkov.</h2>
   </div>
   <div class="gmnfc-footer">
      <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
   </div>
</div>
HTML;
    }

	public function potrebnaPrijava(){
		$plugin_url = GMNFC_PLUGIN_URL;
		$homepath = home_url();
		return <<<HTML
		<link rel="stylesheet" href="$plugin_url/assets/page-resources/form-style.css">
		<div class="gmnfc-quiz">
   <div class="gmnfc-header">
      <img src="$plugin_url/assets/logo.png" alt="logo">
      <h2>Kulturni maraton II. Gimnazije</h2>
   </div>
   <div class="gmnfc-main">
      <h2 style="color: white; font-family: Arial, Helvetica, sans-serif">Stran trenutno ni dosegljiva, saj niste prijavljeni. Pojdite nazaj na <b><a style="color: inherit; font-family: inherit;" href="$homepath">glavno stran</a></b>, se prijavite in poskusite znova.</h2>
   </div>
   <div class="gmnfc-footer">
      <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
   </div>
</div>
HTML;
	}

	public function alreadyAnswered($correctmsg) {
		$plugin_url = GMNFC_PLUGIN_URL;

		if(isset($_GET['home'])){
			if($_GET['home'] == 'true') {
				return wp_redirect( home_url() );
			}
		}

		return <<<HTML
		<link rel="stylesheet" href="$plugin_url/assets/page-resources/form-style.css">
		<div class="gmnfc-quiz">
   <div class="gmnfc-header">
      <img src="$plugin_url/assets/logo.png" alt="logo">
      <h2>Kulturni maraton II. Gimnazije</h2>
   </div>
   <div class="gmnfc-main">
   <a class="nazaj-btn" href="?home=true" home="true"><- Nazaj</a>
     <h2 style="color: white; font-family: Arial;">$correctmsg</h2>
   </div>
   <div class="gmnfc-footer">
      <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
   </div>
</div>
HTML;
	}

	public function skipPrevented(){
		$plugin_url = GMNFC_PLUGIN_URL;

		if(isset($_GET['home'])){
			if($_GET['home'] == 'true') {
				return wp_redirect( home_url() );
			}
		}

		return <<<HTML
		<link rel="stylesheet" href="$plugin_url/assets/page-resources/form-style.css">
		<div class="gmnfc-quiz">
   <div class="gmnfc-header">
      <img src="$plugin_url/assets/logo.png" alt="logo">
      <h2>Kulturni maraton II. Gimnazije</h2>
   </div>
   <div class="gmnfc-main">
   <a class="nazaj-btn" href="?home=true" home="true">Domov</a>
     <h2 style="color: white; font-family: Arial;">
     <p style="text-align: center; color:var(--notice-color);">
    	Za dostop do te naloge morate najprej rešiti prejšnjo nalogo. Brez preskakovanja!
	</p>
</h2>
   </div>
   <div class="gmnfc-footer">
      <p>&copy; 2024 Kulturni maraton II. Gimnazije</p>
   </div>
</div>
HTML;
	}

	private function handleInputs($result){
		$inputHtml = "";

		$answers = explode(";", $result[0]->Odgovor);

		if(count($answers) > 1 && $result[0]->Tip != "Text" && $result[0]->Tip != "Number"){
			switch($result[0]->Tip){
				case "Radio":
					foreach($answers as $answer){
						$inputHtml .= '<input type="radio" name="answer" id="' . $result[0]->ID_NALOGA . '"><label for="' . $result[0]->ID_NALOGA . '">' . $answer . '</label><br>';
					}
					break;
				case "Checkbox":
					foreach($answers as $answer){ // TODO: Fix the checkbox input names for POST (IF checkboxes will be needed)
						$inputHtml .= '<input type="checkbox" name="answer" id="' . $result[0]->ID_NALOGA . '"><label for="' . $result[0]->ID_NALOGA . '">' . $answer . '</label><br>';
					}
					break;

				case "Select":
					$inputHtml .= '<select name="answer" id="' . $result[0]->ID_NALOGA . '">';
					foreach($answers as $answer){
						$inputHtml .= '<option value="' . $answer . '">' . $answer . '</option>';
					}
					$inputHtml .= '</select>';

					break;

			}

		}
		else {
			//$inputHtml .= '<input type="' . $result[0]->Tip . '" name="answer" id="' . $result[0]->ID_NALOGA . '">';
			if($result[0]->Tip == "Upload"){

				/*
				 <label for="file-upload" class="file-label">
			        <span class="upload-btn">Choose File</span>
			        <span class="file-name"></span>
				</label>
				 * */


				$inputHtml .= '<label for="' . $result[0]->ID_NALOGA .'" class="file-label">';
				$inputHtml .= '<span class="file-name"></span>';
				$inputHtml .= '<span class="upload-btn">Izberi datoteko</span>';
				$inputHtml .= '</label>';
				// upload button that only allows videos and one file per upload
				$inputHtml .= '<input type="file" name="file_upload" id="' . $result[0]->ID_NALOGA .'">
';
				// ("mp4", "avi", "mov", "wmv", "flv", "mkv");
				// "mp3", "wav", "flac", "ogg", "m4a"
				/*
				document.getElementById('file-upload').addEventListener('change', function() {
			    var fileName = this.files[0].name;
			    document.querySelector('.file-name').textContent = fileName;
				*/

				$inputHtml .= '<script>';
				$inputHtml .= 'document.getElementById("' . $result[0]->ID_NALOGA .'").addEventListener("change", function() {var fileName = this.files[0].name; document.querySelector(".file-name").textContent = fileName;});';
				$inputHtml .= '</script>';
			}
			else if($result[0]->Tip == "Scan"){
				$inputHtml .= '<input type="text" name="answer" id="' . $result[0]->ID_NALOGA . '">';
			}
			else if($result[0]->Tip == "Text" || $result[0]->Tip == "Number"){
				$inputHtml .= '<div class="text-wrapper"> <input type="text" name="answer" id="' . $result[0]->ID_NALOGA . '"> </div>';
			}
		}
		return $inputHtml;
	}


	private function debug_rebuildDB($rebuild) {
		if ($rebuild == "true"){
			global $wpdb;

			$table_name = $wpdb->prefix . "NFC_Hunt_Dijak";
			$table_name2 = $wpdb->prefix . "NFC_Hunt_Naloga";
			$table_name3 = $wpdb->prefix . "NFC_Hunt_Odgovori";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			require_once GMNFC_PLUGIN_PATH . 'includes/db-gmnfc.php';

			// run db_setup() if the tables do not exist
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name){
				db_setup();
				return;
			}

			// get rid of all foreign keys first
			$wpdb->query("ALTER TABLE $table_name3 DROP FOREIGN KEY FK_odgovorUnD");
			$wpdb->query("ALTER TABLE $table_name3 DROP FOREIGN KEY FK_odgovorUnN");
			$wpdb->query("ALTER TABLE $table_name DROP FOREIGN KEY FK_dijakUnD");
			$wpdb->query("ALTER TABLE $table_name2 DROP FOREIGN KEY FK_nalogaUnP");

			// drop all tables
			$wpdb->query("DROP TABLE $table_name");
			$wpdb->query("DROP TABLE $table_name2");
			$wpdb->query("DROP TABLE $table_name3");

			// create the tables again
			db_setup();
		}
	}

	private function debug_testUsers($testusers) {
		if($testusers == "true"){
			global $wpdb;

			$check_table = $wpdb->prefix . "NFC_Hunt_Dijak";
			$sql = "SELECT * FROM $check_table";
			$results = $wpdb->get_results($sql);

			if(count($results) > 0){
				return '<h2 style="color: orangered;">Database already has users!</h2>';
			}

			$table_name = $wpdb->prefix . "users";

			$sql = "SELECT ID FROM $table_name";
			$results = $wpdb->get_results($sql);

			foreach($results as $result){
				$this->proccessUser($result->ID);
			}

			return '<h2>Database has been filled with users!</h2>';
		}

		return '<h2>Argument does not meet the criteria.</h2>';
	}

	private function debug_emptyAnswerDB(){
		global $wpdb;

		$table_name = $wpdb->prefix . "NFC_Hunt_Odgovori";

		$sql = "TRUNCATE TABLE $table_name";

		$wpdb->query($sql);
	}

}
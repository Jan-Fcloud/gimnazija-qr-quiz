<?php

class GMNFC_Forms{
	public function __construct(){

	}

	public function submitAnswer($user_id, $question_id, $answer){
		global $wpdb;

		$table_name = $wpdb->prefix . 'NFC_Hunt_Naloga';
		$table_name2 = $wpdb->prefix . 'NFC_Hunt_Dijak';
		$table_name3 = $wpdb->prefix . 'NFC_Hunt_Odgovori';

		// get user's ID_DIJAK
		$user = $wpdb->get_results("SELECT ID_DIJAK, Tocke FROM $table_name2 WHERE FK_UserID = $user_id");
		$user = $user[0];
		$user_id = $user->ID_DIJAK;

		// check if the user has answered already
//		$check = $wpdb->get_results("SELECT * FROM $table_name3 WHERE FK_DijakID = $user_id AND FK_NalogaID = $question_id AND Pravilno = 1");
//		if(count($check) > 0){
//			return true;
//		}

			$naloga_id = $question_id;

			// get the post_id for the question
			$question = $wpdb->get_results("SELECT FK_PostID, Tocke FROM $table_name WHERE ID_NALOGA = $question_id");
			$question = $question[0];
			$question_id = $question->FK_PostID;

			// check if the answer is correct
			$correct = get_post_meta($question_id, 'odgovor', true);

			//return "<h1>$correct</h1>";

			if(str_contains($correct, ";")){
				//$answers = str_replace(";", "", $correct);
				$answers = explode(";", $correct);
			}else{
				$answers = $correct;
			}

			$correct_bool = false;

			if(!is_array($answers)){
				$answers = array($answers);
			}
			foreach($answers as $a){
				if(strtolower($a) == strtolower($answer)){
					$correct_bool = true;
				}
			}

		// insert the answer
		$wpdb->insert($table_name3,
			array(
				'FK_DijakID' => $user_id,
				'FK_NalogaID' => $naloga_id,
				'Odgovor' => $answer,
				'Pravilno' => 0
			));

			// if the answer is correct, add points
			if($correct_bool){

				// recheck this part since time got to me and I can't afford to redo the whole thing
				$ze_odgovorili = get_post_meta($question_id, 'odgovorili', true);
				$ze_odgovorili = explode(";", $ze_odgovorili);
				if(in_array($user_id, $ze_odgovorili)){
					return true;
				}


				//$points = $wpdb->get_results("SELECT Tocke FROM $table_name WHERE ID_NALOGA = $question_id");
				//$points = $points[0];
				$points = $question->Tocke;

				//$dijak_tocke = $wpdb->get_results("SELECT Tocke FROM $table_name2 WHERE ID_DIJAK = $user_id");
				//$dijak_tocke = $dijak_tocke[0];
				$dijak_tocke = $user->Tocke;

				$dijak_tocke += $points;

				$wpdb->update($table_name2,
					array(
						'Tocke' => $dijak_tocke
					),
					array(
						'ID_DIJAK' => $user_id
					));

				$wpdb->update($table_name3,
					array(
						'Pravilno' => 1
					),
					array(
						'FK_DijakID' => $user_id,
						'FK_NalogaID' => $naloga_id
					));

				update_post_meta($question_id, 'odgovorili' ,get_post_meta($question_id, 'odgovorili', true) . ";" . $user_id );

				return true;
			}

			return false;

		}

		public function handleUpload($user_id, $question_id){

			if (isset($_POST['gmnfc-submit'])) {
				// Check if files were uploaded
					global $wpdb;
					$table_name3 = $wpdb->prefix . 'NFC_Hunt_Odgovori';
					$table_name2 = $wpdb->prefix . 'NFC_Hunt_Dijak';

					// get user's ID_DIJAK
					$user = $wpdb->get_results("SELECT ID_DIJAK FROM $table_name2 WHERE FK_UserID = $user_id");
					$user = $user[0];
					$dijak_id = $user->ID_DIJAK;

					$post_answer_meta = get_post_meta(get_the_ID(), 'odgovorili', true);
					$odgovorili_this = explode(";", $post_answer_meta);

					if(in_array($dijak_id, $odgovorili_this)){
						echo 'Datoteko ste že naložili. Če želite naložiti novo, se obrnite na info točko.';
						return false;
					}

					$upload_overrides = array('test_form' => false);

					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
					}

					$uploaded_file = wp_handle_upload($_FILES['file_upload'], $upload_overrides);

					/*if (!empty($uploaded_file['error'])) {
						// Handle upload error
						echo 'Upload failed: ' . $uploaded_file['error'];
						echo 'Prosim oglasite se na info točki.';

						return false;
					} else {*/
						// File uploaded successfully
						// echo 'File uploaded successfully: ' . $uploaded_file['file'];
						$wpdb->insert($table_name3,
							array(
								'FK_DijakID' => $dijak_id,
								'FK_NalogaID' => $question_id,
								'Odgovor' => 'file-' . $uploaded_file['url'],
								'Pravilno' => 1
							));

						$post_id = get_the_ID();
						update_post_meta($post_id, 'odgovorili' ,get_post_meta($post_id, 'odgovorili', true) . ";" . $dijak_id );

						// get current points of the dijak
						$dijak_tocke = $wpdb->get_results("SELECT Tocke FROM $table_name2 WHERE FK_UserID = $user_id");
						$dijak_tocke = $dijak_tocke[0];
						$dijak_tocke = $dijak_tocke->Tocke;

						$post_tocke = get_post_meta($post_id, 'tocke', true);

						$wpdb->update($table_name2,
							array(
								'Tocke' => $dijak_tocke + $post_tocke
							),
							array(
								'ID_DIJAK' => $user_id
							));

						return true;
					//}

				} else {
					// echo 'Please select a file to upload.';
				}
		}

			public function scanSubmit($user_id, $question_id){
				global $wpdb;
				$table_name3 = $wpdb->prefix . 'NFC_Hunt_Odgovori';
				$table_name2 = $wpdb->prefix . 'NFC_Hunt_Dijak';

				// get user's ID_DIJAK
				$user = $wpdb->get_results("SELECT ID_DIJAK FROM $table_name2 WHERE FK_UserID = $user_id");
				$user = $user[0];
				$dijak_id = $user->ID_DIJAK;

				$post_answer_meta = get_post_meta(get_the_ID(), 'odgovorili', true);
				$odgovorili_this = explode(";", $post_answer_meta);

				if(in_array($dijak_id, $odgovorili_this)){
					return false;
				}

				$wpdb->insert($table_name3,
					array(
						'FK_DijakID' => $dijak_id,
						'FK_NalogaID' => $question_id,
						'Odgovor' => '--action->scan--',
						'Pravilno' => 1
					));

				$post_id = get_the_ID();
				update_post_meta($post_id, 'odgovorili' ,get_post_meta($post_id, 'odgovorili', true) . ";" . $dijak_id );

				// get current points of the dijak
				$dijak_tocke = $wpdb->get_results("SELECT Tocke FROM $table_name2 WHERE FK_UserID = $user_id");
				$dijak_tocke = $dijak_tocke[0];
				$dijak_tocke = $dijak_tocke->Tocke;

				$post_tocke = get_post_meta($post_id, 'tocke', true);

				// echo $dijak_tocke . ' ----- ' . $post_tocke;

				$wpdb->update($table_name2,
					array(
						'Tocke' => $dijak_tocke + $post_tocke
					),
					array(
						'ID_DIJAK' => $dijak_id
					));

				return true;
			}

}
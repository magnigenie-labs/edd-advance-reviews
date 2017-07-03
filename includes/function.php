<?php

class eddAr {

    public function __construct() {
        $eddar = get_option('edd_settings');
        if (!empty($eddar['enabled'])) {
            if ($eddar['enabled'] == 1) {
                add_action('wp_enqueue_scripts', array($this, 'eddar_scripts'));
                add_action('edd_after_download_content', array($this, 'edd_reviews_button'));
                add_action('wp_ajax_edd_add_reviews', array($this, 'edd_add_reviews'));

                add_shortcode('purchasedReviews', array($this, 'perchased_reviews'));
                if (!empty($eddar['purchased_review_access'])) {
                    if ($eddar['purchased_review_access'] == 1) {
                        add_action('edd_purchase_history_header_before', array($this, 'link_to_purchase_reviews'));
                    }
                }
                add_action('wp_enqueue_scripts', array($this, 'edd_advance_reviews_style'));
            }
        }
        //Check if woocommerce plugin is installed.
        add_action('admin_notices', array($this, 'check_required_plugins'));

        add_filter("plugin_action_links_" . EDDAR_BASE, array($this, 'add_action_links'));

//-----------admin section hook goes here---------

        add_filter('edd_settings_tabs', array($this, 'eddpl_tab'));
        add_filter('edd_registered_settings', array($this, 'edd_advance_settings'));
    }

    public function eddar_scripts() {

        wp_enqueue_style('edd-advance-reviews-css', plugin_dir_url(__FILE__) . 'css/edd-advance-reviews-css.css');
        wp_enqueue_style('edd-advance-reviews-font-css', 'https://fonts.googleapis.com/css?family=Open+Sans');
        wp_enqueue_style('magnific-popup-css', plugin_dir_url(__FILE__) . 'css/magnific-popup.css');
        wp_enqueue_style('jquery.rateyo.min-css', plugin_dir_url(__FILE__) . 'css/jquery.rateyo.min.css');
        wp_enqueue_script('edd-advance-reviews-js', plugin_dir_url(__FILE__) . ('js/edd-advance-reviews-js.js'), array('jquery'), '1.0.0', true);
        wp_enqueue_script('jquery.magnific-popup-js', plugin_dir_url(__FILE__) . ('js/jquery.magnific-popup.js'), array('jquery'), '1.0.0', true);
        wp_enqueue_script('jquery.rateyo-js', plugin_dir_url(__FILE__) . ('js/jquery.rateyo.js'), array('jquery'), '1.0.0', true);
        wp_localize_script('edd-advance-reviews-js', 'eddReviews', array('ajaxUrl' => admin_url('admin-ajax.php')));
    }

    static function insert_page() {
        $page = get_page_by_title('Purchased Reviews');
        if (empty($page)) {
            // Create post object
            $my_post = array(
                'post_title' => 'Purchased Reviews',
                'post_content' => '[purchasedReviews]',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
                'post_type' => 'page',
            );
            wp_insert_post($my_post);
        }
        // Insert the post into the database
    }

    //Check if woocommerce is installed and activated
    public function check_required_plugins() {
        if (!is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')) {
            ?>
            <div id="message" class="error">
                <p>EDD Advance Reviews requires <a href="https://easydigitaldownloads.com/" target="_blank">Easy Digital Downloads</a> to be activated in order to work. Please install and activate it.</p>
            </div>
            <?php
            deactivate_plugins('/edd-advance-reviews/advance-reviews.php');
        }
    }

//--settings link in plugins page-----
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=download&page=edd-settings&tab=eddpl') . '">General Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

//---- define the edd_after_purchase_history callback 
    public function link_to_purchase_reviews() {
        echo "<a href='purchased-reviews'>Click here for purchased reviews</a>";
    }

    public function edd_add_reviews() {
        global $post;
        global $wpdb;
        $rating = $_POST['edd-star-rating'];
        $reason = $_POST['reason'];
        $comment = $_POST['comment'];
        $commentId = $_POST['commentId'];
        $postType = $_POST['posttype'];
        $user_id = get_current_user_id();
        $metakey = "edd-reviews-" . $user_id;
        $date = date('jS \of F Y');
        $nick_name = get_user_meta($user_id, $key = 'nickname', $single = true);


        $postcomment_value = array(
            'rating' => $rating,
            'reason' => $reason,
            'comment' => $comment,
            'userId' => $user_id,
            'date' => $date
        );

        if ($postType == "update") {
            $checkupdate = update_post_meta($commentId, $metakey, $postcomment_value);


            $allData = $wpdb->get_results("
        SELECT * FROM $wpdb->postmeta
        WHERE meta_key = 'edd-reviews-$user_id' AND 
        post_id = $commentId");


            if (!empty($allData)) {
                foreach ($allData as $data) {
                    global $post;
                    $value = unserialize($data->meta_value);
                    $userId = $value['userId'];
                    $rating = $value['rating'];
                    $reason = $value['reason'];
                    $comment = $value['comment'];
                    $date = $value['date'];
                    $commentId = $post->ID;
                    $nick_name = get_user_meta($userId, $key = 'nickname', $single = true);

                    $sendData = array(
                        'status' => 'update',
                        'rating' => $rating,
                        'nickname' => $nick_name,
                        'date' => $date,
                        'reason' => $reason,
                        'comment' => $comment
                    );
                    echo wp_json_encode($sendData);
                    exit;
                }
            }
        } else {
            $checkinsert = update_post_meta($commentId, $metakey, $postcomment_value);
        }



        if ($checkinsert != false) {
            $html = '<div id="reviews-box-' . $user_id . '" class = "edd-reviews-box">
    <span class = "ratingValue">  Rated <strong><span id="rated-' . $user_id . '">' . $rating . '</span></strong> out of 5 </span> &#9733;&#9733;&#9733;&#9733;&#9733;
      
     <a title="Edit" href="#test-form" commentId="' . $commentId . '"user_id="' . $user_id . '"rating="' . $rating . '"reason="' . $reason . '"comment="' . $comment . '" class="update-form update-btn">&#9997;</a>

    <p>
        <strong><span id="nickname-' . $user_id . '">' . $nick_name . '</span></strong> 
        <time id=date-' . $user_id . '>' . $date . '</time><br>
        Reason : <span class="reason" id="reason-' . $user_id . '"> ' . $reason . '</span>
    </p>
    <p id="comment-"' . $user_id . '>' . $comment . '</p>
</div>';
            $sendData = array(
                'status' => 'insert',
                'html' => $html
            );
            echo wp_json_encode($sendData);
            exit;
        }
    }

    public function edd_reviews_button() {
        global $post;
        global $wpdb;

        $eddar = get_option('edd_settings');
        $commentId = $post->ID;
        $user_id = get_current_user_id();
        $allData = get_post_meta($commentId, 'edd-reviews', false);

        $allData = $wpdb->get_results("
        SELECT * FROM $wpdb->postmeta
        WHERE meta_key LIKE '%edd-reviews-%' AND 
        post_id = $commentId");

        if (is_user_logged_in()) {
            if (edd_has_user_purchased($user_id, $commentId)) {
                if (!empty($allData)) {
                    foreach ($allData as $data) {
                        $total[] = unserialize($data->meta_value);
                    }
                    foreach ($total as $single) {
                        $checkId[] = $single['userId'];
                    }
                    if (!empty($checkId)) {
                        if (!in_array($user_id, $checkId)) {
                            echo "<a class='popup-with-form edd-reviews-btn' href='#test-form'>Write a review</a>";
                        }
                    }
                } else {
                    echo "<a class='popup-with-form edd-reviews-btn' href='#test-form'>Write a review</a>";
                }
            }
        }


        $eddar = get_option('edd_settings');

        !empty($eddar['review_modal_title']) ? $revmodaltitle = $eddar['review_modal_title'] : $revmodaltitle = "Review This Item";
        !empty($eddar['review_modal_desc']) ? $revmodaldesc = $eddar['review_modal_desc'] : $revmodaldesc = "Please leave your rating.reason and comment about this item to help the author.";


        echo "<form method='post' id='test-form' class='white-popup-block mfp-hide edd-form' onsubmit='return add_reviews();'>
        <div class='main-loader'></div>
        <span class='edd-loader'></span>
            <div class='edd-header'>
                <lable><strong>" . $revmodaltitle . "</strong> </lable>
            </div>
            <fieldset style='border:0;'>
            <section class='edd-section'>
                <lable>" . $revmodaldesc . "</lable>
                </section>
                       <div class='edd-error-msg'></div>
                <section class='edd-section'>
                    <div class='edd-lable' style='display:inline-block'>Rating <span class='edd-imp'>*</span>  </div>
                    <div class='edd-field edd-star-display'>   
                        <input type='hidden' name='edd-star-rating' class='edd-star-rating' value=''>
                        <div class='rateyo-readonly-widg'></div>
                        </div>
                </section>
                <section class='edd-section'>
                    <div class='edd-lable'>Reason for your rating<span class='edd-imp'>*</span></div>
                    <div class='edd-field'>
                        <select name='reason' id='edd-reason'>
                            <option value=''>--choose reason--</option>
                            <option value='Service & customer support'>Service & customer support</option>
                            <option value='Quality'>Quality</option>
                            <option value='Functionality'>Functionality</option>
                            <option value='User interface'>User interface</option>
                            <option value='User friendly'>User friendly</option>
                            <option value='Bug'>Bug</option>
                        </select>
                        
                    </div>
                </section>
                <section class='edd-section'>
                    <div class='edd-lable'>Comments (min. 30 characters) <span class='edd-imp'>*</span>   <span id='charNum'></span></div>
                  
                    <div class='edd-field'>
                      <input type='hidden' name='commentId' id='commentId' value='$commentId'>
                        <textarea name='comment'  id='edd-comment' onkeyup='countChar(this)' placeholder='Please put your comments here to help the author'></textarea>
                    </div>
                </section>
            </fieldset>
            <footer class='edd-footer'>
                <button class='edd-btn cancel-btn'  type='button'>Cancel</button>
                <button class='edd-btn save-btn' postType='insert' type='submit'>Submit</button>
            </footer>
        </form>";


        if (!empty($allData)) {
            foreach ($allData as $data) {
                global $post;
                $value = unserialize($data->meta_value);
                $userId = $value['userId'];
                $rating = $value['rating'];
                $reason = $value['reason'];
                $comment = $value['comment'];
                $date = $value['date'];
                $commentId = $post->ID;


                $nick_name = get_user_meta($userId, $key = 'nickname', $single = true);

                !empty($eddar['review_update_access']) ? $review_update_access = $eddar['review_update_access'] : $review_update_access = "";

                if (($review_update_access == 1) && ($userId == $user_id)) {
                    $update_btn = '<a title="Edit" commentId="' . $commentId . '"user_id="' . $userId . '"rating="' . $rating . '"reason="' . $reason . '"comment="' . $comment . '" class="update-form update-btn" href="#test-form">&#9997;</a>';
                } else {
                    $update_btn = null;
                }

                echo '<div id="reviews-box-' . $userId . '" class = "edd-reviews-box">
    <span class = "ratingValue">  Rated <strong> <span id="rated-' . $userId . '">' . $rating . '</span></strong> out of 5 </span> &#9733;&#9733;&#9733;&#9733;&#9733;' . $update_btn . '
  
    <p>
        <strong><span id="nickname-' . $userId . '">' . $nick_name . '</span></strong> 
        <time id=date-' . $userId . '>' . $date . '</time><br>
        Reason : <span class="reason" id=reason-' . $userId . '>' . $reason . '</span>
    </p>
    <p id=comment-' . $userId . '>' . $comment . '</p>
</div>';
            }
        }
    }

    public function edd_advance_reviews_style() {
        wp_enqueue_style('custom-style', plugin_dir_url(__FILE__) . 'css/edd-advance-reviews-css.css');
        $eddar = get_option('edd_settings');


        !empty($eddar['btn_bg_color']) ? $btnbgclr = $eddar['btn_bg_color'] : $btnbgclr = "";
        !empty($eddar['btn_color']) ? $btnclr = $eddar['btn_color'] : $btnclr = "";
        !empty($eddar['btn_font_size']) ? $btnfontsize = $eddar['btn_font_size'] : $btnfontsize = "";
        !empty($eddar['review_width']) ? $revwidth = $eddar['review_width'] : $revwidth = "";
        !empty($eddar['review_bg_color']) ? $revbgclr = $eddar['review_bg_color'] : $revbgclr = "";
        !empty($eddar['review_txt_color']) ? $revtxtclr = $eddar['review_txt_color'] : $revtxtclr = "";
        !empty($eddar['review_font_size']) ? $revfontsize = $eddar['review_font_size'] : $revfontsize = "";
        !empty($eddar['modal_bg_color']) ? $modalbgclr = $eddar['modal_bg_color'] : $modalbgclr = "";
        !empty($eddar['modal_txt_color']) ? $modaltxtclr = $eddar['modal_txt_color'] : $modaltxtclr = "";
        !empty($eddar['modal_font_size']) ? $modalfontsize = $eddar['modal_font_size'] : $modalfontsize = "";
        !empty($eddar['update_btn_bg_color']) ? $update_btn_bg_color = $eddar['update_btn_bg_color'] : $update_btn_bg_color = "";
        !empty($eddar['cancel_btn_bg_color']) ? $cancel_btn_bg_color = $eddar['cancel_btn_bg_color'] : $cancel_btn_bg_color = "";
        !empty($eddar['modal_btn_color']) ? $modal_btn_color = $eddar['modal_btn_color'] : $modal_btn_color = "";
        !empty($eddar['modal_btn_font_size']) ? $modal_btn_font_size = $eddar['modal_btn_font_size'] : $modal_btn_font_size = "";

        $custom_css = ".edd-reviews-btn{color: {$btnclr}; font-size:{$btnfontsize}px;
                        background: {$btnbgclr};
                }.cancel-btn{background-color:{$cancel_btn_bg_color};}.save-btn{background-color:{$update_btn_bg_color};}.edd-btn{color:{$modal_btn_color}; font-size:{$modal_btn_font_size}px;}.edd-reviews-box{width:{$revwidth}%; background:{$revbgclr}; color:{$revtxtclr}; font-size:{$revfontsize}px;}.mfp-auto-cursor .mfp-content {
    background: {$modalbgclr};
}.edd-lable,.edd-section lable,.edd-header lable{color: {$modaltxtclr}; font-size:{$modalfontsize}px;}}";
        wp_add_inline_style('custom-style', $custom_css);
    }

    public function perchased_reviews() {
        global $wpdb;
        global $post;
        $commentId = $post->ID;
        $payments = edd_get_users_purchases(get_current_user_id(), 20, true, 'any');
        $eddar = get_option('edd_settings');
        $revmodaltitle = $eddar['review_modal_title'];
        $revmodaldesc = $eddar['review_modal_desc'];
        if (is_user_logged_in()) {
            if ($eddar['purchased_review_access'] == 1) {
                if (!empty($payments)) {
                    echo '<table id="edd_user_history" class="edd-table">
			<thead>
				<tr class="edd_purchase_row">
                                        <th class="">Product ID(s)</th>
                                        <th class="">Reviews date(s)</th>
					<th class="">Product(s)</th>
					<th class="">Rating(s)</th>
					<th class="">Reason(s)</th>
					<th class="">Comment(s)</th>
					<th class="">Action(s)</th>
				</tr>
			</thead>
				<tbody>';

                    foreach ($payments as $payment) {
                        $allDownloads = get_post_meta($payment->ID, '_edd_payment_meta', true);
                        $product = array();
                        foreach ($allDownloads['cart_details'] as $download) {
                            $title = $download['name'];
                            $postID = $download['id'];
                            if (!in_array($postID, $product)) {
                                array_push($product, $postID);
                            }
                        }
                    }

                    foreach ($product as $singleID) {
                        $link = get_page_link($singleID);
                        $user_id = get_current_user_id();

                        $allData = $wpdb->get_results("
        SELECT * FROM $wpdb->postmeta
        WHERE meta_key = 'edd-reviews-$user_id' AND 
        post_id = $singleID");
                        if (!empty($allData)) {
                            foreach ($allData as $data) {
                                $value = unserialize($data->meta_value);
                                $rating = $value['rating'];
                                $date = $value['date'];
                                $reason = $value['reason'];
                                $comment = $value['comment'];
                                $newcmnt = substr($comment, 0, 15);

                                echo the_terms($singleID, 'download_tag', 'Tags: ', ', ', '');
                                echo '<tr class="edd_purchase_row">
                    <td class="">' . $singleID . '</td>
                    <td class="table-date-' . $singleID . '">' . $date . '</td>
                    <td class="">' . $title . '</td>
                    <td class="table-rating-' . $singleID . '">' . $rating . '</td>
                    <td class="table-reason-' . $singleID . '">' . $reason . '</td>
                    <td class="table-comment-' . $singleID . '">' . $newcmnt . '...</td>
                    <td class="" style="text-align:center;"> <a title="Edit" href="#test-form" commentId="' . $singleID . '"user_id="' . $user_id . '"rating="' . $rating . '"reason="' . $reason . '"comment="' . $comment . '" class="table-form">&#9997;</a></td>
				</tr>';
                            }
                        } else {
                            echo '<tr class="edd_purchase_row">
                    <td class="">' . $singleID . '</td>
                    <td class="">' . $date . '</td>
                    <td class="">' . $title . '</td>
                    <td class=""></td>
                    <td class=""></td>
                    <td class=""></td>
                    <td class=""><a href="' . $link . '">see all reviews</a></td>
				</tr>';
                        }
                    }
                    echo '</tbody></table>';
                    echo "<form method='post' id='test-form' class='white-popup-block mfp-hide edd-table-form' onsubmit='return add_table_reviews();'>
        <div class='main-loader'></div>
        <span class='edd-loader'></span>
            <div class='edd-header'>
                <lable><strong>" . $revmodaltitle . "</strong> </lable>
            </div>
            <fieldset style='border:0;'>
            <section class='edd-section'>
                <lable>" . $revmodaldesc . "</lable>
                </section>
                       <div class='edd-error-msg'></div>
                <section class='edd-section'>
                    <div class='edd-lable' style='display:inline-block'>Rating <span class='edd-imp'>*</span>  </div>
                    <div class='edd-field edd-star-display'>   
                        <input type='hidden' name='edd-star-rating' class='edd-star-rating' value=''>
                        <div class='rateyo-readonly-widg'></div>
                        </div>
                </section>
                <section class='edd-section'>
                    <div class='edd-lable'>Reason for your rating<span class='edd-imp'>*</span></div>
                    <div class='edd-field'>
                        <select name='reason' id='edd-reason'>
                            <option value=''>--choose reason--</option>
                            <option value='Service & customer support'>Service & customer support</option>
                            <option value='Quality'>Quality</option>
                            <option value='Functionality'>Functionality</option>
                            <option value='User interface'>User interface</option>
                            <option value='User friendly'>User friendly</option>
                            <option value='Bug'>Bug</option>
                        </select>
                    </div>
                </section>
                <section class='edd-section'>
                    <div class='edd-lable'>Comments (min. 30 characters) <span class='edd-imp'>*</span>   <span id='charNum'></span></div>
                  
                    <div class='edd-field'>
                      <input type='hidden' name='commentId' id='commentId' value='$commentId'>
                        <textarea name='comment'  id='edd-comment' onkeyup='countChar(this)' placeholder='Please put your comments here to help the author'></textarea>
                    </div>
                </section>
            </fieldset>
            <footer class='edd-footer'>
                <button class='edd-btn cancel-table-btn'  type='button'>Cancel</button>
                <button class='edd-btn save-btn' postType='update' type='submit'>Update</button>
            </footer>
        </form>";
                }
            }
        }
    }

//--admin settings

    public function eddpl_tab($tabs) {
        $tabs['eddpl'] = 'EDD Advance Reviews';
        return $tabs;
    }

    public function edd_advance_settings($settings) {
        $settings['eddpl'] = apply_filters('edd_settings_eddpl', array(
            'eddmd_enabled' => array(
                'id' => 'enabled',
                'name' => __('Enable', 'edd-advance-reviews'),
                'desc' => __('Enable edd advance reviews plugin.', 'edd-advance-reviews'),
                'type' => 'checkbox',
            ),
            array(
                'id' => 'Review Button Settings',
                'name' => '<h3>' . __('Review Button Settings', 'edd-advance-reviews') . '</h3>',
                'type' => 'header'
            ),
            'btn_bg_color' => array(
                'id' => 'btn_bg_color',
                'name' => __('Review button background color', 'edd-advance-reviews'),
                'desc' => __('Choose the background color you want to use for the review button.', 'edd-advance-reviews'),
                'type' => 'color',
                'default' => '#ccc'
            ),
            'btn_color' => array(
                'id' => 'btn_color',
                'name' => __('Review button text color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the review button.', 'edd-advance-reviews'),
                'type' => 'color',
                'default' => '#fff'
            ),
            'btn_font_size' => array(
                'id' => 'btn_font_size',
                'name' => __('Review button font size', 'edd-advance-reviews'),
                'desc' => __('px', 'edd-advance-reviews'),
                'type' => 'number',
            ),
            array(
                'id' => 'Review Modal Button Settings',
                'name' => '<h3>' . __('Review Modal Button Settings', 'edd-advance-reviews') . '</h3>',
                'type' => 'header'
            ),
            'update_btn_bg_color' => array(
                'id' => 'update_btn_bg_color',
                'name' => __('Update button background color', 'edd-advance-reviews'),
                'desc' => __('Choose the background color you want to use for the update button.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'cancel_btn_bg_color' => array(
                'id' => 'cancel_btn_bg_color',
                'name' => __('Cancel button background color', 'edd-advance-reviews'),
                'desc' => __('Choose the background color you want to use for the cancel button.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'modal_btn_color' => array(
                'id' => 'modal_btn_color',
                'name' => __('Update & cancel button text color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the update & cancel button.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'modal_btn_font_size' => array(
                'id' => 'modal_btn_font_size',
                'name' => __('Update & cancel button font size', 'edd-advance-reviews'),
                'desc' => __('px', 'edd-advance-reviews'),
                'type' => 'number',
            ),
            array(
                'id' => 'Review Box Settings',
                'name' => '<h3>' . __('Review Box Settings', 'edd-advance-reviews') . '</h3>',
                'type' => 'header'
            ),
            'review_update_access' => array(
                'id' => 'review_update_access',
                'name' => __('Update Review Access', 'edd-advance-reviews'),
                'desc' => __('Give update access for reviews to customer', 'edd-advance-reviews'),
                'type' => 'checkbox',
            ),
            'review_width' => array(
                'id' => 'review_width',
                'name' => __('Review box width', 'edd-advance-reviews'),
                'desc' => __('%', 'edd-advance-reviews'),
                'type' => 'number',
            ),
            'review_bg_color' => array(
                'id' => 'review_bg_color',
                'name' => __('Review box background color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the review box.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'review_txt_color' => array(
                'id' => 'review_txt_color',
                'name' => __('Review box text color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the text of review box.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'review_font_size' => array(
                'id' => 'review_font_size',
                'name' => __('Review box font size', 'edd-advance-reviews'),
                'desc' => __('px', 'edd-advance-reviews'),
                'type' => 'number',
            ),
            array(
                'id' => 'Review Modal Settings',
                'name' => '<h3>' . __('Review Modal Settings', 'edd-advance-reviews') . '</h3>',
                'type' => 'header'
            ),
            'modal_bg_color' => array(
                'id' => 'modal_bg_color',
                'name' => __('Modal background color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the background of modal box.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'modal_txt_color' => array(
                'id' => 'modal_txt_color',
                'name' => __('Modal text color', 'edd-advance-reviews'),
                'desc' => __('Choose the color you want to use for the text of modal box.', 'edd-advance-reviews'),
                'type' => 'color',
            ),
            'modal_font_size' => array(
                'id' => 'modal_font_size',
                'name' => __('Modal font size', 'edd-advance-reviews'),
                'desc' => __('px', 'edd-advance-reviews'),
                'type' => 'number',
            ),
            'review_modal_title' => array(
                'id' => 'review_modal_title',
                'name' => __('Review modal title', 'edd-advance-reviews'),
                'desc' => __('Write the title you want to use for the header of review modal', 'edd-advance-reviews'),
                'type' => 'text',
            ),
            'review_modal_desc' => array(
                'id' => 'review_modal_desc',
                'name' => __('Review modal description', 'edd-advance-reviews'),
                'desc' => __('Write the description you want to use for the review modal', 'edd-advance-reviews'),
                'type' => 'textarea',
            ),
            array(
                'id' => 'Purchase Reviews Page Settings',
                'name' => '<h3>' . __('Purchase Reviews Page Settings', 'edd-advance-reviews') . '</h3>',
                'type' => 'header'
            ),
            'purchased_review_access' => array(
                'id' => 'purchased_review_access',
                'name' => __('Purchased review page access', 'edd-advance-reviews'),
                'desc' => __('Give access for purchased reviews page to customer.', 'edd-advance-reviews'),
                'type' => 'checkbox',
            ),
                )
        );
        return $settings;
    }

}

new eddAr();

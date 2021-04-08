<?php
function my_custom_post_app() {
    $labels = array(
        'name'               => _x( 'App', 'post type general name' ),
        'singular_name'      => _x( 'App', 'post type singular name' ),
        'add_new'            => _x( 'Nieuw artikel', 'Artikels' ),
        'add_new_item'       => __( 'Artikel toevoegen' ),
        'edit_item'          => __( 'Artikel aanpassen' ),
        'new_item'           => __( 'Artikel toevoegen' ),
        'all_items'          => __( 'Alle artikels' ),
        'view_item'          => __( 'Open Artikel' ),
        'search_items'       => __( 'Zoeken' ),
        'not_found'          => __( 'Geen resultaten gevonden' ),
        'not_found_in_trash' => __( 'Geen resultaten gevonden' ),
        'parent_item_colon'  => '',
        'menu_name'          => 'App'
    );
    $args = array(
        'labels'        => $labels,
        'description'   => 'Een custom post type',
        'public'        => true,
        'show_in_rest'  => true,
        'menu_position' => 6,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments'),
        'has_archive'   => true,
        'rewrite'       => array('slug' => 'app'),
        'taxonomies'    => array('category')
    );
    register_post_type( 'app', $args );
}
add_action( 'init', 'my_custom_post_app' );

function app_add_custom_box() {
    $screens = ['app'];
    foreach ( $screens as $screen ) {
        add_meta_box(
            'app_box_id',                 // Unique ID
            'Event',      // Box title
            'app_custom_box_html',  // Content callback, must be of type callable
            $screen                            // Post type
        );
    }
    function app_custom_box_html( $post ) {
        ?>
        <label for="app_field_datum">Dag:</label>
        <input required type="date" name="app_field_datum" id="app_field_datum" value="<?php echo esc_attr(get_post_meta(get_the_ID(), "app_field_datum", true)); ?>"/><br><br>
        <label for="app_field_begintijd">Begintijd:</label>
        <input style="width: 50px" required type="number" min="8" max="17" name="app_field_begintijd" id="app_field_begintijd" value="<?php echo esc_attr(get_post_meta(get_the_ID(), "app_field_begintijd", true)); ?>"/><input style="width: 60px" type="number" placeholder=":00" readonly/><br><br>
        <label for="app_field_eindtijd">Eindtijd:</label>
        <input style="display: none; width: 50px" required type="number" min="8" max="18" name="app_field_eindtijd" id="app_field_eindtijd" value="<?php echo esc_attr(get_post_meta(get_the_ID(), "app_field_eindtijd", true)); ?>"/><input id="placeholder" style="width: 60px; display: none" type="number" placeholder=":00" readonly/>
        <script>
            var beginTijd = document.getElementById("app_field_begintijd")
            var eindTijd = document.getElementById("app_field_eindtijd")
            var placeholder = document.getElementById("placeholder")
            beginTijd.addEventListener("change", () => {
                if (beginTijd.value == 8){
                    eindTijd.style.display = ""
                    placeholder.style.display = ""
                }
                var x = parseInt(beginTijd.value) + 1
                eindTijd.value = x
                eindTijd.min = x
            })
        </script>
        <?php
    }
}
add_action( 'add_meta_boxes', 'app_add_custom_box' );

function app_save_postdata( $post_id ) {
    $field_list = [
        "app_field_datum",
        "app_field_begintijd",
        "app_field_eindtijd"
    ];
    foreach ($field_list as $fieldname) {
        if (array_key_exists($fieldname, $_POST)) {
            update_post_meta(
                $post_id,
                $fieldname,
                $_POST[$fieldname]
            );
        }
    }
}
add_action( 'save_post', 'app_save_postdata' );

add_action( 'rest_api_init', 'adding_post_meta_rest' );
function adding_post_meta_rest() {
    register_rest_field( 'app',
        'cf',
        array(
            'get_callback'      => 'post_meta_callback',
            'update_callback'   => null,
            'schema'            => null,
        )
    );
}

function post_meta_callback( $post, $field_name, $request) {
    return get_post_meta( $post['id'] );
}

add_filter('user_contactmethods', 'my_user_contactmethods');

function my_user_contactmethods($user_contactmethods){

    $user_contactmethods['tel'] = 'Telefoonnummernummer:';
    $user_contactmethods['bedrijf'] = 'bedrijf:';
    $user_contactmethods['thema'] = 'thema:';
    $user_contactmethods['planning_poker'] = 'planningpoker:';

    return $user_contactmethods;
}

function register_user_meta(){
    $metavalues = ['tel', 'bedrijf', 'thema', 'planning_poker'];
    foreach ($metavalues as $metavalue){
        register_meta( 'user', $metavalue, [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ]);
    }
}
add_filter('init', 'register_user_meta');

add_action('rest_api_init', 'wp_rest_user_endpoints');

function wp_rest_user_endpoints($request) {
    /**
     * Handle Register User request.
     */
    register_rest_route('wp/v2', 'users/register', array(
        'methods' => 'POST',
        'callback' => 'wc_rest_user_endpoint_handler',
    ));
}
function wc_rest_user_endpoint_handler($request = null) {
    $response = array();
    $parameters = $request->get_json_params();
    $username = sanitize_text_field($parameters['username']);
    $email = sanitize_text_field($parameters['email']);
    $password = sanitize_text_field($parameters['password']);
    $voornaam = sanitize_text_field($parameters['voornaam']);
    $achternaam = sanitize_text_field($parameters['achternaam']);
    $telefoonnummer = sanitize_text_field($parameters['telefoonnummer']);
    $bedrijf = sanitize_text_field($parameters['bedrijf']);
    $error = new WP_Error();
    if (empty($username)) {
        $error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    if (empty($email)) {
        $error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    if (empty($password)) {
        $error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    $user_id = username_exists($username);
    if (!$user_id && email_exists($email) == false) {
        $user_id = wp_insert_user(array(
              'user_login' => $username,
              'user_pass' => $password,
              'user_email' => $email,
              'first_name' => $voornaam,
              'last_name' => $achternaam,
              'tel' => $telefoonnummer,
              'bedrijf' => $bedrijf,
              'thema' => 'theme-light',
              'planning_poker' => 'classic',
              'role' => 'subscriber',
        ));
            $response['code'] = 200;
            $response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");

    } else {
        $error->add(406, __("User already exists", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    return new WP_REST_Response($response, 123);
}

add_theme_support( 'post-thumbnails' );

add_action('rest_api_init', 'register_rest_images' );
function register_rest_images(){
    register_rest_field( array('app'),
        'fimg_url',
        array(
            'get_callback'    => 'get_rest_featured_image',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
function get_rest_featured_image( $object, $field_name, $request ) {
    if( $object['featured_media'] ){
        $img = wp_get_attachment_image_src( $object['featured_media'], 'app-thumb' );
        return $img[0];
    }
    return false;
}

add_action( 'fire_this_function', 'my_custom_callback_function');
function my_custom_callback_function( $return_args, $post_id){
    $data = array(
        'ID' => $post_id,
        'post_title' => $return_args->post->post_title. "blblb",
        'post_content' => $return_args->post->post_content
    );

    wp_update_post( $data );
    //run your custom logic in here
}
?>
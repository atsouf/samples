//when someone edits a template on the root site of o multisite build, on save copy the template to the rest sites - subdomains

function deventum_update_ae_templates()
    {
        global $wpdb;


        $current_id = get_the_ID();
        $name = get_post_field('post_name', $current_id);
       $templates = $wpdb->get_results("SELECT meta_value FROM dt_postmeta WHERE post_id=' $current_id' AND meta_key='_elementor_data'");
       $updated_value = $templates[0]->meta_value;

       for($i=2; $i<8;$i++){
              $table_name='dt_'.$i.'_posts';
              $table_update='dt_'.$i.'_postmeta';
          $results=$wpdb->get_results("SELECT ID FROM $table_name WHERE post_name='$name'");
          $data_update = array('meta_value' => $updated_value);
        $data_where = array('post_id' => $results[0]->ID,
                            'meta_key'=> '_elementor_data');
         $wpdb->update($table_update, $data_update, $data_where);
        }
    }
}
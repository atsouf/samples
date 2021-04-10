//create on wordpress backend input fields

function deventum_save_bulk_edit_hook() {
    global $wpdb;


    // well, if post IDs are empty, it is nothing to do here
    if( empty( $_POST[ 'post_ids' ] ) ) {
        die();
    }

    $start = $_POST['from_date'];
    $end = $_POST['to_date'];
    $start_date_r = implode('', array_reverse(explode('/', $start)));
    $end_date_r = implode('', array_reverse(explode('/', $end)));
    $start_date = implode('', explode('-', $start_date_r));
    $end_date = implode('', explode('-', $end_date_r));



if($start_date < $end_date) {//check validation of dates

    // for each post ID
    foreach( $_POST[ 'post_ids' ] as $id ) {
        if( $_POST[ 'status' ] =='Make Unavailable') {
            $tours = $wpdb->get_results( "SELECT * FROM dt_st_tour_availability WHERE status='available' AND post_id='$id'");
            foreach ( $tours as $tour ) { //scan all the tours
                $availability_id = $tour->id;
                $current_date = implode('', explode('/', date('Y/m/d', $tour->check_in)));


                if($start_date <= $current_date && $current_date <= $end_date){ //check if date is between the range

                    $data_update = array( 'status' => "unavailable" );
                    $data_where = array('id' => $availability_id);
                    $wpdb->update( 'dt_st_tour_availability', $data_update, $data_where);
                } //end check date range
            }//end of scan all the tours
        } elseif($_POST[ 'status' ]=='Make Available') { // check for dates with status available
            $tours = $wpdb->get_results( "SELECT * FROM dt_st_tour_availability WHERE status='unavailable' AND post_id='$id'");
            foreach ( $tours as $tour ) { //scan all the tours
                $availability_id = $tour->id;
                $current_date = implode('', explode('/', date('Y/m/d', $tour->check_in)));
                if($start_date <= $current_date && $current_date <= $end_date){ //check if date is between the range
                    $data_update = array( 'status' => "available" );
                    $data_where = array('id' => $availability_id);
                    $wpdb->update('dt_st_tour_availability', $data_update, $data_where);
                } //end check date range
            }//end of scan all the tours
        } //end chck for mark
    } //end check validation of dates



    }

    die();
}


//the ajax action

//jQuery(function($){
    $( 'body' ).on( 'click', 'input[name="bulk_edit"]', function() {

        // let's add the WordPress default spinner just before the button
        $( this ).after('<span class="spinner is-active"></span>');


        // define: prices, featured products and the bulk edit table row
        var bulk_edit_row = $( 'tr#bulk-edit' ),
            post_ids = new Array();
            from_date = bulk_edit_row.find( 'input[name="fromdate"]' ).val();
            console.log(from_date);
            to_date = bulk_edit_row.find( 'input[name="todate"]' ).val();
            console.log(to_date);
              var e = document.getElementById("status");
              var status = e.options[e.selectedIndex].innerHTML;
         //   status = bulk_edit_row.find( 'input[name="statusOption"]' ).text();
            console.log(status);

        // now we have to obtain the post IDs selected for bulk edit
        bulk_edit_row.find( '#bulk-titles' ).children().each( function() {
            post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
        });

        // save the data with AJAX
       $.ajax({
            url: ajaxurl, // WordPress has already defined the AJAX url for us (at least in admin area)
            type: 'POST',
            data: {
                action: 'deventum_save_bulk', // wp_ajax action hook
                post_ids: post_ids, // array of post IDs
                from_date: from_date,
                to_date: to_date,// new price
                status: status, // new value for product featured

            }
        });
    });
});



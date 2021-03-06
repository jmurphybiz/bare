<?php

/**
 * Class adds dashboard widgets to wp-admin dashboard
 *
 * @package Leads
 * @subpackage Dashboard
 */

class Leads_Dashboard {

    static $user_id;
    static $hidden;

    public function __construct() {

        $enable = Leads_Settings::get_setting('wpl-main-enable-dashboard', 1);
        if (!$enable) {
            return;
        }

        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));

        add_action('admin_enqueue_scripts', array(__CLASS__, 'load_static_vars'));

        add_action('admin_enqueue_scripts', array(__CLASS__, 'register_admin_scripts'));

        add_action('admin_head', array(__CLASS__, 'add_inline_header_scripts'));

        add_action( 'wp_ajax_leads_update_monitored_lists' , array( __CLASS__ , 'ajax_update_lists' ) );

    }

    /**
     * Loading Static Variables while in Dashboard screen
     */
    public static function load_static_vars( $hook ) {
        if ('index.php' != $hook) {
            return;
        }

        self::$user_id = get_current_user_id();
        self::$hidden = get_user_option(
            'metaboxhidden_dashboard',
            self::$user_id
        );
        self::$hidden =  ( isset(self::$hidden) && is_array(self::$hidden) ) ? self::$hidden : array() ;
    }

    /**
     * Add dashboard widgets
     */
    public static function add_dashboard_widgets() {

        if (!current_user_can('activate_plugins')) {
            return;
        }

        $custom_dashboard_widgets = array(
            'wp-lead-performance' => array(
                'title' => __('Lead Performance', 'inbound-pro'),
                'callback' => array(__CLASS__, 'display_lead_performance_report')
            ),
            'wp-lead-latest-leads' => array(
                'title' => __('Latest Leads', 'inbound-pro'),
                'callback' => array(__CLASS__, 'display_latest_leads_widget'),
            ),
            'wp-lead-list-performance' => array(
                'title' => __('List Performance', 'inbound-pro'),
                'callback' => array(__CLASS__, 'display_list_performance_widget'),
            ),
        );

        foreach ($custom_dashboard_widgets as $widget_id => $options) {
            wp_add_dashboard_widget(
                $widget_id,
                $options['title'],
                $options['callback']
            );
        }
    }

    /**
     * Enqueue scripts and styles for dashboard
     * @param $hook
     */
    public static function register_admin_scripts($hook) {

        if ('index.php' != $hook) {
            return;
        }

        wp_enqueue_script('jquery-cookie', WPL_URLPATH . 'assets/js/jquery.cookie.js', array(), null, true);
        wp_enqueue_script('flot', WPL_URLPATH . 'assets/js/jquery.flot.js', array(), null, false);
        wp_enqueue_script('flot-stack', WPL_URLPATH . 'assets/js/jquery.flot.stack.js', array(), null, false);
        wp_enqueue_script('flot-time', WPL_URLPATH . 'assets/js/jquery.flot.time.js', array(), null, false);
        wp_enqueue_script('flot-axislabels', WPL_URLPATH . 'assets/js/jquery.flot.axislabels.js', array(), null, false);
        wp_enqueue_script('lead-flot-functions', WPL_URLPATH . 'assets/js/lead-flot-functions.js', array(), null, false);

        wp_enqueue_script('custom-dashboard-js', WPL_URLPATH . 'assets/js/custom-dashboard.js', array(), false, true);
        wp_enqueue_style('custom-dashboard-css', WPL_URLPATH . '/assets/css/wpl.dashboard.css');

        /* load fontawesome */
        wp_enqueue_style('fontawesome', INBOUNDNOW_SHARED_URLPATH . 'assets/fonts/fontawesome/css/font-awesome.min.css');

    }

    /**
     * Adding inline header scripts and styling  - powers lead quick search in admin bar
     */
    public static function add_inline_header_scripts() {
        if (!class_exists('Inbound_Pro_Plugin')) {
            return;
        }
        ?>
        <style type="text/css">
            #wpadminbar .adminbar-leads-search .lead-quick-search {
                display: inline !important;
            }

            #wpadminbar .adminbar-leads-search .fa {
                font-family: 'FontAwesome'
            }

            #wpadminbar .adminbar-leads-search form {
                display: inline !important;
                width: auto;
                height: auto;
            }

            #wpadminbar .adminbar-leads-search .lead-quick-search input, #wpadminbar .adminbar-leads-search .lead-quick-search input, #wpadminbar .adminbar-leads-search .lead-quick-search input:focus {
                width: 1px;
                -webkit-transition: all .3s cubic-bezier(0, 0, .5, 1.5);
                transition: all .3s cubic-bezier(0, 0, .5, 1.5);
                font-size: 9px;
                height: 25px;
                margin-top: 3px;
                padding-bottom: 0px;
                padding-top: 0px;
                padding-left: 3px;
                color: #eee;
                background-color: #444;
                margin-right: 5px;
                border: none;
                line-height: 30px;
                display: none;
            }

        </style>
        <section class="lead-quick-search" style="display:none" method="get">
            <form action="<?php echo admin_url('edit.php?s=hudson.atwell%40gmail.com&post_status=all&post_type=wp-lead'); ?>">
                <input name="s" type="search" placeholder="<?php _e('Search Leads', 'inbound-pro'); ?>">
                <input type="hidden" name="post_type" value="wp-lead">
            </form>
        </section>

        <script type="text/javascript">
            jQuery(document).ready(function () {
                var on = false;
                running_ajax = new Object();

                jQuery('.lead-quick-search').prependTo('.adminbar-leads-search .ab-item');
                jQuery('.adminbar-leads-search .fa-search').click(function () {
                    if (!on) {
                        jQuery('.lead-quick-search input').show().animate({
                            width: '70%'
                        }, 200);
                        on = true;
                    } else {
                        jQuery('.lead-quick-search input').animate({
                            width: '0px'
                        }, 200).hide();
                        on = false;
                    }

                });

                jQuery('.plus-minus-toggle').on('click', function() {
                    jQuery(this).toggleClass('collapsed');
                });

                jQuery('#toggle-lead-list-selection').on('click' , function() {
                    jQuery('#list-performance-options').toggle();
                });

                jQuery('#list-performance-options input:checkbox').on('change' , function() {

                    if (typeof running_ajax['leads_update_monitored_lists'] != 'undefined' ) {
                        running_ajax['leads_update_monitored_lists'].abort();
                    }

                    running_ajax['leads_update_monitored_lists'] = jQuery.ajax({
                        url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                        type: "post",
                        data: {
                            action: 'leads_update_monitored_lists',
                            lists: jQuery('input[name="dashboard-list-performance\[\]"]:checked').serialize()
                        },
                        dataType: 'html',
                        success: function(data) {
                            jQuery('#response').html(data);
                        }
                    });
                });

            });
        </script>
        <?php

    }

    /**
     *
     */
    public static function display_lead_performance_report() {
        global $wpdb;

        /*if the widget isn't supposed to display, echo a short message and exit*/
        if (in_array('wp-lead-performance' , self::$hidden)) {
            /*output a message for when the widget is re-enabled*/
            echo '<div style="height: 220px; text-align: center;">
                    <h3 style="position: relative; top:49%">' . __('Refresh page to enable. ', 'inbound-pro') . '</h3>
                </div>';
            return;
        }

        $count_posts = wp_count_posts('wp-lead');
        $url = site_url();

        $start_current = date("Y-m-01"); // start of current month
        $end_current = date("Y-m-t", strtotime('last day of this month')); // end of current month
        
        $previous_month_start = date("Y-m-01", strtotime("previous month"));
        $previous_month_end = date("Y-m-d", strtotime("-1 month"));

        $this_month = self::count_leads_by_time($start_current, $end_current);
        $last_month = self::count_leads_by_time($previous_month_start, $previous_month_end);

        $all_time_leads = $count_posts->publish;
        $all_lead_text = ($all_time_leads == 1) ? "Lead" : "Leads";
        $leads_today = Leads_Dashboard::get_lead_count_from_today('wp-lead');
        $leads_today_text = ($leads_today == 1) ? "Lead" : "Leads";
        $month_comparasion = $this_month - $last_month;

        if ($month_comparasion < 0) {
            $month_class = 'negative-leads';
            $sign = "";
            $sign_text = __('Decrease ', 'inbound-pro');
        } elseif ($month_comparasion === 0) {
            $month_class = 'no-change';
            $sign = "";
            $sign_text = __('No Change ', 'inbound-pro');
        } else {
            $month_class = 'positive-leads';
            $sign = "+";
            $sign_text = __('Increase ', 'inbound-pro');
        }
        echo '<div id="lead-before-dashboard">';
        do_action('wp_lead_before_dashboard');
        echo "</div>";

        $clean_dates = date("m", strtotime("first day of previous month"));
        $clean_date_two = date("m");
        ?>

        <!--[if lte IE 8]>
        <script language="javascript" type="text/javascript" src="/wp-content/plugins/lead-dashboard-widgets/assets/js/flot/excanvas.min.js"></script><![endif]-->

        <div class="wp_leads_dashboard_widget">

            <script type="text/javascript">
                /* <![CDATA[ */
                window.data1 = [ <?php echo self::get_lead_graph_data($clean_date_two, 'this-month'); ?> ];
                window.data2 = [ <?php echo self::get_lead_graph_data($clean_dates, 'last-month'); ?> ];
                /* ]]> */
            </script>
            <div id="flot-placeholder" style='width: 100%; height: 250px; margin: 10px auto 0px; padding: 0px; position: relative; margin-bottom:10px;'></div>


            <div id="wp-leads-stat-boxes">
                <div class='wp-leads-today'>
                    <a class="data-block widget-block" alt='Click to View Todays Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead&current_date"; ?>">
                        <section>
                            <?php echo $leads_today; ?>
                            <br><?php echo $leads_today_text; ?>
                            <br><strong><?php _e('Today', 'inbound-pro'); ?></strong>
                        </section>
                    </a>
                </div>
                <div class='wp-leads-this-month'>
                    <a class="data-block widget-block" alt='Click to View This Months Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead&current_month"; ?>">
                        <section>
                            <?php echo $this_month; ?>
                            <br><?php echo $all_lead_text; ?>
                            <br><strong><?php _e('This Month', 'inbound-pro'); ?></strong>
                        </section>
                    </a>
                </div>
                <div class='wp-leads-all-time'>
                    <a class="data-block widget-block" title='Click to View All Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead"; ?>">
                        <section>
                            <?php echo $all_time_leads; ?>
                            <br><?php _e('Leads', 'inbound-pro'); ?>
                            <strong><?php _e('All Time', 'inbound-pro'); ?></strong>
                        </section>
                    </a>
                </div>
                <div class="wp-leads-change-box" style="text-align: center;">
                    <small class='<?php echo $month_class; ?>'><?php echo "<span>" . $sign . $month_comparasion . "</span> " . $sign_text; ?><?php _e('Since Last Month', 'inbound-pro'); ?></small>
                </div>
            </div>
            <!-- <div class='wp-leads-last-month'>
			last month: <?php echo $last_month; ?>

			<?php echo $this_month - $last_month; ?>
			</div>	-->
        </div>
        <?php
    }

    /**
     * Displays the latest converted leads.
     */
    public static function display_latest_leads_widget() {

        /*if the widget isn't supposed to display, echo a short message and exit*/
        if (in_array('wp-lead-latest-leads' , self::$hidden)) {
            /*output a message for when the widget is re-enabled*/
            echo '<div style="height: 220px; text-align: center;">
                    <h3 style="position: relative; top:49%">' . __('Refresh page to enable. ', 'inbound-pro') . '</h3>
                </div>';
            return;
        }

        ?>
        <div id='leads-list'>
            <?php $r = new WP_Query(apply_filters('widget_posts_args', array(
                'posts_per_page' => 20,
                'post_type' => 'wp-lead',
                'post_status' => 'publish')));

            if ($r->have_posts()) : ?>
                <ul id='lead-ul'>
                    <?php while ($r->have_posts()) : $r->the_post(); ?>
                        <li><?php $id = get_the_ID();
                            $first_name = get_post_meta($id, 'wpleads_first_name', true);
                            $last_name = get_post_meta($id, 'wpleads_last_name', true);
                            $name = $first_name . " " . $last_name;
                            if ($name === " ") {
                                $name = get_the_title($id);
                            }

                            $avatar_link = Leads_Post_Type::get_gravatar($id, 20);
                            $img = "<img src=\"$avatar_link\" width=\"20\" height=\"20\" class=\"latest-leads-mini-avatar\" />";
                            ?>

                            <?php edit_post_link($img . $name); ?> on <?php the_time('F jS, Y'); ?>
                            (<?php the_title(); ?>)
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

    }

    /**
     * Shows the lead generation performance of lists compared to this time yesterday, and by this date last month
     */
    public static function display_list_performance_widget() {
        global $wpdb;

        /*if the widget isn't supposed to display, echo a short message and exit*/
        if (in_array('wp-lead-list-performance' , self::$hidden)) {
            /*output a message for when the widget is re-enabled*/
            echo '<div style="height: 220px; text-align: center;">
                    <h3 style="position: relative; top:49%">' . __('Refresh page to enable. ', 'inbound-pro') . '</h3>
                </div>';
            return;
        }

        $time = new DateTime(date_i18n('Y-m-d G:i:s T'));
        $today = $time->format('Ymd');
        $this_month = $time->modify('first day of this month')->format('Ym');

        $lists = Inbound_Leads::get_lead_lists_as_array();
        $monitored_lists =  get_option( 'leads-dashboard-list-monitoring' , array() );

        ?>


        <table class="leads-list-performance-table">
            <thead>
            <tr>
                <td>
                    <?php _e('Lead List' , 'inbound-pro'); ?>
                </td>
                <td>
                    <?php _e('Current Day' , 'inbound-pro'); ?>
                </td>
                <td>
                    <?php _e('Current Month' , 'inbound-pro'); ?>
                </td>
            </tr>
            </thead>
            <tbody>
            <?php
            $table_count_number = 1;
            foreach ($monitored_lists as $key => $list_id ) {

                $list = get_term_by( 'id' , $list_id , 'wplead_list_category' );

                /* get days */
                $todays_leads = count(self::get_list_event_data($list_id, '12 am today'));
                $yesterdays_leads = count(self::get_list_event_data($list_id, '12 am today', '12 am today -1 days'));
                $yesterdays_leads_at_this_time = count(self::get_list_event_data($list_id, 'now -1 days', '12 am today'));

                if ($yesterdays_leads_at_this_time == 0 && $todays_leads == 0) {
                    /*if there were no leads yesterday and there's none today, set the displayed output to 0%*/
                    $day_change = '0%';
                    $day_difference_class = 'lead-count-no-change';
                } else if ($yesterdays_leads_at_this_time == 0 && $todays_leads > 0) {
                    /*if there were no leads yesterday but there are leads today, set the increase to 100%*/
                    $day_change = '100%';
                    $day_difference_class = 'lead-count-increase';
                } else {
                    /*if there were leads yesterday, get the percent difference between now and this time yesterday*/
                    $day_change = ($todays_leads / $yesterdays_leads_at_this_time * 100) - 100;

                    /*set the appropriate css class for the difference*/
                    if ($day_change > 0) {
                        $day_difference_class = 'lead-count-increase';
                    } else if ($day_change < 0) {
                        $day_difference_class = 'lead-count-decrease';
                    } else {
                        $day_difference_class = 'lead-count-no-change';
                    }

                    $day_change = round($day_change, 2) . '%';
                }

                /* get months */
                $this_months_leads = count(self::get_list_event_data($list_id, 'first day of this month'));
                $last_months_leads = count(self::get_list_event_data($list_id, 'first day of this month', 'first day of this month -1 months'));
                $last_month_at_this_time = count(self::get_list_event_data($list_id, 'now -1 months', 'first day of this month'));

                if ($last_month_at_this_time == 0 && $this_months_leads == 0) {
                    /*if there were no leads last month and there's none today, set the displayed output to 0%*/
                    $month_change = '0%';
                    $month_difference_class = 'lead-count-no-change';
                } else if ($last_month_at_this_time == 0 && $this_months_leads > 0) {
                    /*if there were no leads last month but there are leads today, set the increase to 100%*/
                    $month_change = '100%';
                    $month_difference_class = 'lead-count-increase';
                } else {
                    /*if there were leads last month, get the percent difference between the leads so far and the leads at this time last month*/
                    $month_change = ($this_months_leads / $last_month_at_this_time * 100) - 100;

                    /*set the appropriate css class for the difference*/
                    if ($month_change > 0) {
                        $month_difference_class = 'lead-count-increase';
                    } else if ($month_change < 0) {
                        $month_difference_class = 'lead-count-decrease';
                    } else {
                        $month_difference_class = 'lead-count-no-change';
                    }

                    $month_change = round($month_change, 2) . '%';
                }
                ?>
                <tr>
                    <td>
                        <?php echo $list->name; ?>
                    </td>
                    <td>
                        <span  class="list-stat">
                            <?php
                            if ( defined('INBOUND_PRO_PATH') ) {
                                ?>

                                <a href='<?php echo admin_url('index.php?action=inbound_generate_report&class=Inbound_Events_Report&range=1&event_name=inbound_add_list&show_graph=false&list_id=' . $list_id . '&title='.  $list->name .'&tb_hide_nav=true&TB_iframe=true&width=1000&height=600'); ?>' class='thickbox inbound-thickbox'>
                                    <?php echo $todays_leads; ?>
                                </a>
                                <?php
                            }   else {
                                 echo $todays_leads;
                            }
                            ?>
                        </span>
                         <span class="stat list-performance-change-pill <?php echo $day_difference_class; ?>" title="<?php printf(_n('By this time yesterday %s lead was added.', 'At this time yesterday %s leads had been added.', 'inbound-pro'), number_format_i18n($yesterdays_leads_at_this_time));
                         printf(_n('%s lead was added to &quot;' . $list->name . '&quot; yesterday. ', 'A total of %s leads were added yesterday.', 'inbound-pro'), number_format_i18n($yesterdays_leads)); ?>" data-toggle="tooltip" data-placement="left">
                                <?php echo self::get_formatted_difference($todays_leads , $yesterdays_leads_at_this_time) . ' ('.$yesterdays_leads.')'; ?>
                        </span>
                    </td>
                    <td >
                        <span class="list-stat">
                            <?php
                            if ( defined('INBOUND_PRO_PATH') ) {
                                ?>

                                <a href='<?php echo admin_url('index.php?action=inbound_generate_report&class=Inbound_Events_Report&range='.date('d').'&event_name=inbound_add_list&show_graph=false&list_id=' . $list_id . '&title='.  $list->name .'&tb_hide_nav=true&TB_iframe=true&width=1000&height=600'); ?>' class='thickbox inbound-thickbox'>
                                    <?php echo $this_months_leads; ?>
                                </a>
                                <?php
                            }   else {
                                echo $this_months_leads;
                            }
                            ?>
                        </span>
                        <span class="stat list-performance-change-pill no-wrap <?php echo $month_difference_class; ?>" title="<?php printf(_n('At this day last month, %s lead had been added to', 'At this day last month, %s leads were added. ', $last_month_at_this_time, 'inbound-pro'), number_format_i18n($last_month_at_this_time));
                        printf(_n('Last month %s lead was added to &quot;' . $list->name . '&quot;. ', 'A total of %s leads added last month. ', $last_months_leads, 'inbound-pro'), number_format_i18n($last_months_leads)); ?>" data-toggle="tooltip" data-placement="left">
                               <?php echo self::get_formatted_difference($this_months_leads, $last_month_at_this_time) . ' ('.$last_months_leads.')' ; ?>
                        </span>
                    </td>
                </tr>

                <?php
                $table_count_number++;
            }
            ?>
            </tbody>
        </table>
        <div style="width:100%;text-align:right;">
            <div class="plus-minus-toggle collapsed" id="toggle-lead-list-selection"></div>
        </div>
        <div id="list-performance-options">
            <?php
            foreach ( $lists as $id=>$list_name ) {
                echo '<label><input name="dashboard-list-performance[]" type="checkbox" value="'.$id.'" ' . ( in_array( $id , $monitored_lists) ? 'checked="checked"' : '' ) .'>'.$list_name.'</label><br>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get number of leads added between two datetimes.
     * @param $start_current
     * @param $end_current
     * @return mixed
     */
    public static function count_leads_by_time($start_current, $end_current) {
        global $wpdb;
        global $table_prefix;

        $numposts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(ID) " .
                "FROM {$wpdb->posts} " .
                "WHERE post_status='publish' " .
                "AND post_type= %s " .
                "AND {$table_prefix}posts.post_date BETWEEN %s AND %s",
                'wp-lead', $start_current, $end_current
            )
        );

        return $numposts;
    }

    public static function get_formatted_difference($val_1 , $val_2) {
        $difference = $val_1 - $val_2;

        if (!strstr($difference, '-') && $difference) {
            $difference = '+'.$difference;
        }

        return $difference;
    }


    /**
     * Gets count of new leads generated today
     * @return mixed
     */
    public static function get_lead_count_from_today() {
        global $wpdb;
        global $table_prefix;

        $wordpress_date_time = $timezone_format = _x('Y-m-d', 'timezone date format');
        $wordpress_date_time = date_i18n($timezone_format);
        $wordpress_date = $timezone_day = _x('d', 'timezone date format');
        $wordpress_date = date_i18n($timezone_day);

        $today = $wordpress_date_time; // Corrected timezone
        $tomorrow = date("Y-m-d", strtotime("+2 day")); // Hack to look 2 days ahead


        $numposts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(ID) " .
                "FROM {$wpdb->posts} " .
                "WHERE post_status='publish' " .
                "AND post_type= %s " .
                "AND {$table_prefix}posts.post_date BETWEEN %s AND %s",
                'wp-lead', $today, $tomorrow
            )
        );
        return $numposts;
    }

    /**
     * @param $month
     * @param $type
     */
    public static function get_lead_graph_data($month, $type) {
        global $wpdb;
        global $table_prefix;

        $wordpress_date_time = $timezone_format = _x('Y-m-d', 'timezone date format');
        $wordpress_date_time = date_i18n($timezone_format);
        $wordpress_date = $timezone_day = _x('d', 'timezone date format');
        $wordpress_date = date_i18n($timezone_day);
        $this_year = _x('Y', 'timezone date format');
        $this_year = date_i18n($this_year);
        $loop_count = date('d', strtotime('last day of this month'));


        $final_loop_count = cal_days_in_month(CAL_GREGORIAN, $month, $this_year); // Count of days in month

        /*if last month was december, wind back the year*/
        if ($type === "last-month" && $month == '12') {
            $this_year -= 1;
        }

        //echo $final_loop_count; // How many times to run
        $lead_increment = 0;
        for ($i = 1; $i < $final_loop_count + 1; $i++) {
            // echo "hi" . $i;
            $year = $this_year;
            $day = $i;
            $next_day = $i + 1;
            $m = $month;
            $Date = strtotime($year . "-" . $m . "-" . $day);
            $Date_next = strtotime($year . "-" . $m . "-" . $next_day);
            $clean_date_one = date('Y-m-d', $Date);
            $clean_date_one_formatted = date('Y, n, d', $Date);
            if ($type === "last-month") {
                $Date = strtotime($year . "-" . $m . "-" . $day . ' +1 months');
                $clean_date_one_formatted = date('Y, m, d', $Date);
            }
            $clean_date_two = date('Y-m-d', $Date_next);
            //echo $clean_date_one . "<br>";
            $numposts = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(ID) " .
                    "FROM {$wpdb->posts} " .
                    "WHERE post_status='publish' " .
                    "AND post_type= %s " .
                    "AND {$table_prefix}posts.post_date BETWEEN %s AND %s",
                    'wp-lead', $clean_date_one, $clean_date_two
                )
            );
            $lead_increment += $numposts;
            //echo "Day is: ". $day . " " . $numposts . " on " . $clean_date_one	.	"<br>";
            echo "[gd(" . $clean_date_one_formatted . "), " . $lead_increment . ", " . $numposts . "], ";
        }

    }


    /**
     * Retrieves the leads added to a list between a start date and right now.
     * If second date argument is supplied, leads will be retrieved from a time period starting at
     * the second time arg and ending at the time specified by the first arg.
     */
    public static function get_list_event_data($id, $start_date = 'now', $older_start_date = false) {
        global $wpdb;

        if (empty($id)) {
            return;
        }

        $today = new DateTime(date_i18n('Y-m-d G:i:s T'));
        $dates['end_date'] = $today->format('Y-m-d G:i:s T');
        $today->modify($start_date);
        $dates['start_date'] = $today->format('Y-m-d G:i:s T');

        $table_name = $wpdb->prefix . "inbound_events";

        $query = 'SELECT DISTINCT lead_id';
        $query .= ' FROM ' . $table_name . ' WHERE 1=1 ';
        $query .= ' AND event_name = "inbound_list_add"';
        $query .= ' AND list_id = ' . $id;

        /*if a second date is supplied, that is used as the new start date and $start_date is used as the new end date*/
        if (gettype($older_start_date) == 'string' && !empty($older_start_date)) {

            /* generate dates for previous date-range */
            $today->modify($older_start_date);
            $dates['past_start_date'] = $today->format('Y-m-d G:i:s T');
            $dates['past_end_date'] = $dates['start_date'];

            $query .= ' AND datetime >= "' . $dates['past_start_date'] . '" AND datetime <= "' . $dates['past_end_date'] . '" ';

        } else {

            $query .= ' AND datetime >= "' . $dates['start_date'] . '" AND datetime <= "' . $dates['end_date'] . '" ';
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    public static function get_color_from_string($str) {
        $code = dechex(crc32($str));
        $code = substr($code, 0, 6);
        return $code;

        /*
        function hsl2rgb($H, $S, $V) {
            $H *= 6;
            $h = intval($H);
            $H -= $h;
            $V *= 255;
            $m = $V*(1 - $S);
            $x = $V*(1 - $S*(1-$H));
            $y = $V*(1 - $S*$H);
            $a = [[$V, $x, $m], [$y, $V, $m],
                  [$m, $V, $x], [$m, $y, $V],
                  [$x, $m, $V], [$V, $m, $y]][$h];
            return sprintf("#%02X%02X%02X", $a[0], $a[1], $a[2]);
        }

        function hue($tstr) {
            return unpack('L', hash('adler32', $tstr, true))[1];
        }

        $phrase = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";
        $words = [];
        foreach (explode(' ', $phrase) as $word)
            $words[hue($word)] = $word;
        ksort($words);
        foreach ($words as $h => $word) {
            $col = hsl2rgb($h/0xFFFFFFFF, 0.4, 1);
            printf('<span style="color:%s">%s</span> ', $col, $word);
        }*/
    }


    /**
     *  Ajax listener for saving updated custom field data
     */
    public static function ajax_update_lists() {
        /* parse string */
        parse_str($_POST['lists'] , $array );

        //error_log(print_r($array['dashboard-list-performance'],true));

        /* Update Setting */
        update_option( 'leads-dashboard-list-monitoring' , $array['dashboard-list-performance'] , false );

    }
}

new Leads_Dashboard();

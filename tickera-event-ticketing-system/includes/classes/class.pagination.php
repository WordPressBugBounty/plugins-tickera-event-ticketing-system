<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Pagination' ) ) {

    class TC_Pagination {

        var $total_pages = -1;
        var $limit = 10;
        var $items_title = 'items';
        var $target = "";
        var $page = 1;
        var $adjacents = 2;
        var $showCounter = false;
        var $className = "pagination-links";
        var $parameterName = "page";
        var $pagination;

        // Url Friendly
        var $urlF = false;

        // Buttons next and previous
        var $nextT = "Next";
        var $nextI = "&#187;";
        var $prevT = "Previous";
        var $prevI = "&#171;";
        var $calculate = false;

        /**
         * Total items
         *
         * @param $value
         */
        function items( $value ) {
            $this->total_pages = (int) $value;
        }

        /**
         * How many items to show per page
         *
         * @param $value
         */
        function limit( $value ) {
            $this->limit = (int) $value;
        }

        /**
         * Page to sent the page value
         *
         * @param $value
         */
        function target( $value ) {
            $this->target = $value;
        }

        /**
         * Current page
         *
         * @param $value
         */
        function currentPage( $value ) {
            $this->page = (int) $value;
        }

        /**
         * How many adjacent pages should be shown on each side of the current page?
         *
         * @param $value
         */
        function adjacents( $value ) {
            $this->adjacents = (int) $value;
        }

        /**
         * Show counter
         * @param string $value
         */
        function showCounter( $value = "" ) {
            $this->showCounter = ( $value === true ) ? true : false;
        }

        #to change the class name of the pagination div

        function changeClass( $value = "" ) {
            $this->className = $value;
        }

        function nextLabel( $value ) {
            $this->nextT = $value;
        }

        function nextIcon( $value ) {
            $this->nextI = $value;
        }

        function prevLabel( $value ) {
            $this->prevT = $value;
        }

        function prevIcon( $value ) {
            $this->prevI = $value;
        }

        /**
         * To change the class name of the pagination div
         *
         * @param string $value
         */
        function parameterName( $value = "" ) {
            $this->parameterName = $value;
        }

        /**
         * To change urlFriendly
         *
         * @param string $value
         * @return bool
         */
        function urlFriendly( $value = "%" ) {
            if ( eregi( '^ *$', $value ) ) {
                $this->urlF = false;
                return false;
            }
            $this->urlF = $value;
        }

        function pagination() {}

        function show() {
            if ( ! $this->calculate && $this->calculate() ) {
                echo wp_kses_post( '<span class="displaying-num">' . (int) $this->total_pages . ' ' . esc_html( $this->items_title ) . '</span><div class="tablenav-pages"><span class="' . esc_attr( $this->className ) . '">' . wp_kses_post( $this->pagination ) . "</span></div>\n" );
            }
        }

        function getOutput() {
            if ( ! $this->calculate ) {
                if ( $this->calculate() ) {
                    return '<span class="' . esc_attr( $this->className ) . '">' . wp_kses_post( $this->pagination ) . '</span>\n';
                }
            }
        }

        function get_pagenum_link( $id ) {

            if ( strpos( $this->target, '?' ) === false ) {

                if ( $this->urlF )
                    return str_replace( $this->urlF, $id, $this->target );

                else
                    return "$this->target?$this->parameterName=$id";

            } else {
                return "$this->target&$this->parameterName=$id";
            }
        }

        function calculate() {

            $this->pagination = "";
            $this->calculate == true;
            $error = false;

            if ( $this->urlF and $this->urlF != '%' and strpos( $this->target, $this->urlF ) === false ) {
                //_e("You have specified one wildcard to replace, but does it does not exist in the target", 'tickera-event-ticketing-system');
                $error = true;

            } elseif ( $this->urlF and $this->urlF == '%' and strpos( $this->target, $this->urlF ) === false ) {
                //_e("You must specify the wildcard% target to replace the page number", 'tickera-event-ticketing-system');
                $error = true;
            }

            if ( $this->total_pages < 0 ) {

                //echo "It is necessary to specify the <strong>number of pages</strong> (\$class->items(1000))<br />";
                $error = true;
            }

            if ( $this->limit == null ) {
                //echo "It is necessary to specify the <strong>limit of items</strong> to show per page (\$class->limit(10))<br />";
                $error = true;
            }

            if ( $error ) {
                return false;
            }

            $n = trim( $this->nextT . ' ' . $this->nextI );
            $p = trim( $this->prevI . ' ' . $this->prevT );

            // Setup vars for query.
            $start = ( $this->page )
                ? ( $this->page - 1 ) * $this->limit // First item to display on this page
                : 0; // If no page var is given, set start to 0

            // Setup page vars for display.
            $prev = $this->page - 1; // Previous page is page - 1
            $next = $this->page + 1; // Next page is page + 1
            $lastpage = ceil( $this->total_pages / $this->limit );  // Lastpage is = total pages / items per page, rounded up.
            $lpm1 = $lastpage - 1;   // Last page minus 1

            if ( $lastpage > 1 ) {

                if ( $this->page ) {

                    // Anterior button
                    if ( $this->page > 1 ) {
                        $this->pagination .= '<a href="' . esc_url( $this->get_pagenum_link( 1 ) ) . '" class="first-page">&laquo;</a>&nbsp;<a href="' . esc_url( $this->get_pagenum_link( $prev ) ) . '" class="prev-page">&lsaquo;</a>&nbsp;';

                    } else {
                        $this->pagination .= '<a class="first-page disabled">&laquo;</a>&nbsp;<a class="prev-page disabled">&lsaquo;</a>&nbsp;';
                    }
                }

                for ( $counter = 1; $counter <= $lastpage; $counter++ ) {}

                $this->pagination .= '&nbsp;<span class="paging-input">' . (int) $this->page . ' of <span class="total-pages">' . (int) $lastpage . '</span></span>&nbsp;';

                if ( $this->page ) {

                    if ( $this->page < $counter - 1 ) {
                        $this->pagination .= '&nbsp;<a href="' . esc_url( $this->get_pagenum_link( $next ) ) . '" class="next-page">&rsaquo;</a>&nbsp;<a href="' . esc_url( $this->get_pagenum_link( $lastpage ) ) . '" class="last-page">&raquo;</a>';

                    } else {
                        $this->pagination .= '&nbsp;<a class="next-page disabled">&rsaquo;</a>&nbsp;<a class="last-page disabled">&raquo;</a>';
                    }

                    if ( $this->showCounter ) {
                        $this->pagination .= '<div class="pagination_data">(' . (int) $this->total_pages . ' Pages)</div>';
                    }
                }
            }
            return true;
        }
    }
}

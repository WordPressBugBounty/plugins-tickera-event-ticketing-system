<?php

namespace Tickera;

class TCPDF_EXT extends TCPDF {

    /**
     * Background Image
     *
     * Image: Image absolute url
     * Size: Orientation Size (e.g A4)
     * Placement:
     * 0 = All Pages        `Render background image across pdf pages`
     * 1 = First Page       `Render background image only in first page`
     *
     * @var array
     */
    private $background;

    /**
     * Orientations
     *
     * Values:
     * P = Portrait
     * L = Landscape
     *
     * @var string
     */
    private $orientation;

    function __construct( $orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false, $background = [] ) {
        parent::__construct( $orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa );
        $this->background = $background;
        $this->orientation = $orientation;
    }

    function Header() {

        $placement = isset( $this->background[ 'placement' ] ) ? $this->background[ 'placement' ] : 0;

        if ( $this->background[ 'image' ] && ( ! $placement || $this->page == $placement ) ) {

            // Set variable to eliminate overwrite in multi-page
            $background_size = $this->background[ 'size' ];

            $orientation_sizes = array(
                'P' => array(
                    'A4' => array( 0, 0, 210, 297 ),
                    'A5' => array( 0, 0, 148, 210 ),
                    'A6' => array( 0, 0, 105, 148 ),
                    'A7' => array( 0, 0, 74, 105 ),
                    'A8' => array( 0, 0, 52, 74 ),
                    'ANSI_A' => array( 0, 0, 216, 279 )
                ),
                'L' => array(
                    'A4' => array( 0, 0, 297, 210 ),
                    'A5' => array( 0, 0, 210, 148 ),
                    'A6' => array( 0, 0, 148, 105 ),
                    'A7' => array( 0, 0, 105, 74 ),
                    'A8' => array( 0, 0, 74, 52 ),
                    'ANSI_A' => array( 0, 0, 279, 216 )
                )
            );

            // Custom Size ( Example array format: [ 0, 0, X, Y ] )
            if ( is_array( $this->background[ 'size' ] ) ) {
                $orientation_sizes[ $this->orientation ][ 'custom' ] = array_merge( [ 0, 0 ], $this->background[ 'size' ] );
                $background_size = 'custom';
            }

            $tc_bg_size = $orientation_sizes[ $this->orientation ][ $background_size ];

            // Get the current page break margin
            $bMargin = $this->getBreakMargin();

            // Get current auto-page-break mode
            $auto_page_break = $this->AutoPageBreak;

            // Disable auto-page-break
            $this->SetAutoPageBreak( false, 0 );

            // Set background image
            $this->Image( $this->background[ 'image' ], $tc_bg_size[ 0 ], $tc_bg_size[ 1 ], $tc_bg_size[ 2 ], $tc_bg_size[ 3 ], '', '', '', true, 300, '', false, false, 0, false );

            // Restore auto-page-break status
            $this->SetAutoPageBreak( $auto_page_break, $bMargin );

            // Set the starting point for the page content
            $this->setPageMark();
        }
    }
}
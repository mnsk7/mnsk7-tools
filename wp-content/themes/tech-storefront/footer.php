<?php
/**
* The template for displaying the footer
*
* @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
*
* @package Best_Shop
*/

$social_links = best_shop_get_setting( 'social_links' );
?>
  <footer id="colophon" class="site-footer" itemscope itemtype="https://schema.org/WPFooter">
      <div class="footer-overlay">
      <?php 

      $footer_sidebars = array( 'advanced-footer-widget-1', 'advanced-footer-widget-2', 'advanced-footer-widget-3', 'advanced-footer-widget-4' );
      $active_sidebars = array();
      $sidebar_count   = 0;

      foreach ( $footer_sidebars as $sidebar ) {
          if( is_active_sidebar( $sidebar ) ){
              array_push( $active_sidebars, $sidebar );
              $sidebar_count++ ;
          }
      }

      $tech_storefront_newsletter =  best_shop_get_setting('subscription_shortcode');

      if( $active_sidebars && $sidebar_count > 0 ||  $tech_storefront_newsletter !=='' ){ ?>
          <div class="footer-top">
              <div class="container"><?php

                  if($tech_storefront_newsletter) {
                      ?>
                      <div class="footer-newsletter-section">
                      <?php
                      the_widget('best_shop_newsletter_widget', array('newsletter_shortcode'=> $tech_storefront_newsletter ));
                      ?>
                      </div>
                      <?php

                  } 

                  ?>
                  <div class="grid column-<?php echo esc_attr( $sidebar_count ); ?>">
                  <?php foreach( $active_sidebars as $active ){ ?>
                      <div class="col">
                         <?php dynamic_sidebar( $active ); ?> 
                      </div>
                  <?php } ?>
                  </div>
              </div>
          </div>
      <?php 
      } 

      // MNK7: blok kontakt + dostawa + Instagram (struktura kolumnowa)
      if ( function_exists( 'mnsk7_contact_info_html' ) ) :
      ?>
      <div class="mnsk7-site-footer-block">
          <div class="container">
              <div class="mnsk7-site-footer-block__grid">
                  <div class="mnsk7-site-footer-block__col mnsk7-site-footer-block__col--contact">
                      <?php echo mnsk7_contact_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </div>
                  <div class="mnsk7-site-footer-block__col mnsk7-site-footer-block__col--delivery">
                      <?php
                      if ( function_exists( 'mnsk7_dostawa_vat_html' ) ) {
                          echo mnsk7_dostawa_vat_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      }
                      if ( function_exists( 'mnsk7_delivery_eta_html' ) ) {
                          echo mnsk7_delivery_eta_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      }
                      ?>
                      <p class="mnsk7-site-footer-block__delivery-note"><?php esc_html_e( 'Darmowa dostawa od 300 zł. Tylko Polska.', 'tech-storefront' ); ?></p>
                  </div>
                  <div class="mnsk7-site-footer-block__col mnsk7-site-footer-block__col--instagram">
                      <h4 class="mnsk7-site-footer-block__col-title"><?php esc_html_e( 'Instagram', 'tech-storefront' ); ?></h4>
                      <p><a href="<?php echo esc_url( defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/' ); ?>" target="_blank" rel="noopener">@mnsk7tools</a></p>
                  </div>
              </div>
          </div>
      </div>
      <?php endif; ?>

      <div class="footer-bottom">

          <?php
              if( has_nav_menu( 'footer-menu' ) ): ?>            
          <div class="container">

                  <div class="footer-bottom-menu">
                          <?php
                              wp_nav_menu( array(
                                  'theme_location' => 'footer-menu',
                                  'menu_class'     => 'footer-bottom-links',
                                  'fallback_cb'    => false,
                                  'depth'          => 1,
                              ) );
                          ?>
                  </div> 

          </div>
               <?php 
              endif;
          ?>           

          <div class="container footer-info" style="<?php if(!$social_links){ echo 'text-align:center'; } ?>">
              <?php 
                  best_shop_footer_site_info();

                 best_shop_social_links(true);
              ?> 
          </div>
      </div>

      </div>    
  </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>

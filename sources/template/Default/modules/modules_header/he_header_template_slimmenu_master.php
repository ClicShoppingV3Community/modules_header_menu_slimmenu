<?php
/**
 *
 *  @copyright 2008 - https://www.clicshopping.org
 *  @Brand : ClicShopping(Tm) at Inpi all right Reserved
 *  @Licence GPL 2 & MIT
 *  @licence MIT - Portion of osCommerce 2.4
 *  @Info : https://www.clicshopping.org/forum/trademark/
 *
 */

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;
  use ClicShopping\OM\HTML;

  class he_header_template_slimmenu_master {
    public $code;
    public $group;
    public $title;
    public $description;
    public $sort_order;
    public $enabled = false;
    public $pages;

    public function __construct() {
      $this->code = get_class($this);
      $this->group = basename(__DIR__);

      $this->title = CLICSHOPPING::getDef('modules_header_template_slimmenu_master_title');
      $this->description = CLICSHOPPING::getDef('modules_header_template_slimmenu_master_description');

      $this->show_full_tree = true;
      $this->idname_for_menu = '';
      $this->classname_for_selected = '';
      $this->classname_for_parent = '';

      if (defined('MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_STATUS')) {
        $this->sort_order = MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_SORT_ORDER;
        $this->enabled = (MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_STATUS == 'True');
        $this->pages = MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_DISPLAY_PAGES;
      }
    }

// Create the root unordered list
    public function getCategoriesUlList($rootcatid = 0, $maxlevel = MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LEVEL) {
      $CLICSHOPPING_Db = Registry::get('Db');
      $CLICSHOPPING_Language = Registry::get('Language');
      $CLICSHOPPING_Category = Registry::get('Category');

      $cPath_array = $CLICSHOPPING_Category->getPathArray();
// Modify category query if not fetching all categories (limit to root cats and selected subcat tree)
      if (!$this->show_full_tree ) {
        $parent_query = ' and (c.parent_id = 0';

        if (isset($cPath_array)) {
          $cPath_array_temp = $cPath_array;
          foreach($cPath_array_temp AS $key => $value) {
            $parent_query .= ' OR c.parent_id = "'.$value.'" ';
          }
          unset($cPath_array_temp);
        }
        $parent_query .= ')';
      } else {
        $parent_query = '';
      }

      $Qcategories = 'select c.categories_id,
                             cd.categories_name,
                             c.parent_id
                      from :table_categories c,
                           :table_categories_description cd
                      where c.categories_id = cd.categories_id
                      and cd.language_id = :languages_id
                      and virtual_categories = 0
                      and c.status = 1
                      ' . $parent_query . '
                      order by sort_order,
                      cd.categories_name
                     ';

      $Qmenu = $CLICSHOPPING_Db->prepare($Qcategories);

      $Qmenu->bindInt(':languages_id', $CLICSHOPPING_Language->getId());
//      $Qmenu->setCache('categories-SlimMenu-lang' . $CLICSHOPPING_Language->getId()); // to test

      $Qmenu->execute();

      while ($Qmenu->fetch() !== false) {
//            $categories_image = '<h3>' . HTML::image($CLICSHOPPING_Template->getDirectoryTemplateImages() . $row['categories_image'], HTML::outputProtected($row['categories_name']), 30, 30) . '</a></h3>';
        $table[$Qmenu->valueInt('parent_id')][$Qmenu->valueInt('categories_id')] = $Qmenu->value('categories_name');
      }

      $output = '<ul class="slimmenu">';
      $output .= '<li class="headerTemplateSlimmenMasterIndexTitle">' . HTML::link(CLICSHOPPING::link(), CLICSHOPPING::getDef('module_header_template_slimmenu_master_title')) . '</li>';
      $output .= $this->getCategoriesUlBranch($rootcatid, $table, 0, $maxlevel);

      if (MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LINK == 'True') {
// Close off nested lists
        for ($nest = 0; $nest <= $GLOBALS['this_level']; $nest++) {
//if you need extra links uncomment out the lines below
//                  $output .= '</ul></li>';
//                  $output .= '<ul>';
//                  $output .='<li><a href=" '.CLICSHOPPING::link('products_new.php', '').'" >'.MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_PRODUCTS_NEW.'</a></li>';
//                  $output .='<li><a href=" '.CLICSHOPPING::link('specials.php', '').'" >'.MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_SPECIALS.'</a></li>';
//                  $output .='<li><a href=" '.CLICSHOPPING::link('products_favorites.php', '').'" >'.MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_PRODUCTS_FAVORITES.'</a></li>';
//                  $output .='<span class="slimmenuTitle"><li><a href=" '.CLICSHOPPING::link(null, 'Blog&Categories').'" >'.MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_BLOG.'</a></li></span>';
//                  $output .='<span class="slimmenuContact"><li><a href=" '.CLICSHOPPING::link('contact_us.php', '').'" >'.MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_CONTACT.'</a></li></span>';
//          $output .= '</ul>' . "\n";
          $nest = $nest+1;
        }
      }

      $output .= '</ul>';

      return $output;
    }

// Create the branches of the unordered list
    public function getCategoriesUlBranch($parcat, $table, $level, $maxlevel) {
      $CLICSHOPPING_Category = Registry::get('Category');
      $CLICSHOPPING_rewriteUrl = Registry::get('RewriteUrl');

      $cPath_array = $CLICSHOPPING_Category->getPathArray();

      $list = $table[$parcat];

      if (!is_null($list)) {
        $output = '';

        foreach ($list as $key => $val) {
          if ($GLOBALS['this_level'] != $level) {
            if ($GLOBALS['this_level'] < $level) {
              $output .= "\n" . '<ul>';
            } else {
              for ($nest = 1; $nest <= ($GLOBALS['this_level'] - $level); $nest++) {
                $output .= '</ul></li>' . "\n";
              }
            }

            $GLOBALS['this_level'] = $level;
          } //end if

          if (isset($cPath_array) && in_array($key, $cPath_array) && $this->classname_for_selected) {
            $this_cat_class = ' class="' . $this->classname_for_selected . '"';
          } else {
            $this_cat_class = '';
          }

  //gt   $output .= '<li class="cat_lev_'.$level.'"><a href="';
          //$output .= '<li class="current_'.$level.'"><a href="';
          $output .= '<li><a href="';

          if (!$level) {
            unset($GLOBALS['cPath_set']);
            $GLOBALS['cPath_set'][0] = $key;
            $cPath_new = $key;

          } else {
            $GLOBALS['cPath_set'][$level] = $key;
            $cPath_new =  implode('_', array_slice($GLOBALS['cPath_set'], 0, ($level+1)));
          }

          if ($CLICSHOPPING_Category->getHasSubCategories($key) && $this->classname_for_parent) {
            $this_parent_class = ' class="' . $this->classname_for_parent . '"';
          } else {
            $this_parent_class = '';
          }

          $categories_name = $CLICSHOPPING_rewriteUrl->getCategoryTreeTitle($val);
          $categories_url = $CLICSHOPPING_rewriteUrl->getCategoryTreeUrl($cPath_new);

          $output .= $categories_url . '"' . $this_parent_class . '>' . $categories_name;
          $output .= '</a>';

          if (!$CLICSHOPPING_Category->getHasSubcategories($key)) {
            $output .= '</li>'."\n";
          }

          if ((isset($table[$key])) AND (($maxlevel > $level + 1) OR ($maxlevel == '0'))) {
            $output .= $this->getCategoriesUlBranch($key,$table,$level + 1,$maxlevel);
          }
        } // End while loop
      }

      return $output;
    }

    public function execute() {
      $CLICSHOPPING_Template = Registry::get('Template');
      $CLICSHOPPING_Customer = Registry::get('Customer');

      $content_width = (int)MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_CONTENT_WIDTH;

      $footer_tag = '<script src="' . CLICSHOPPING::link($CLICSHOPPING_Template->getTemplateDefaultJavaScript('slimmenu-master/jquery.slimmenu.min.js')) . '"></script>' . "\n";
      $footer_tag .= '<script defer src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>' . "\n";
      $footer_tag .= '<script> ';
      $footer_tag .= '$(\'ul.slimmenu\').slimmenu( ';
      $footer_tag .= '{ ';
      $footer_tag .= 'resizeWidth: \'800\', ';
      $footer_tag .= 'collapserTitle: \'Main Menu\', ';
      $footer_tag .= 'easingEffect:\'easeInOutQuint\', ';
      $footer_tag .= 'animSpeed:\'medium\', ';
      $footer_tag .= 'indentChildren: true, ';
      $footer_tag .= 'childrenIndenter: \'&raquo;\' ';
      $footer_tag .= '});';
      $footer_tag .= '</script>'."\n";

      $CLICSHOPPING_Template->addBlock($footer_tag, 'footer_scripts');

      $GLOBALS['this_level'] = 0;

      $categories_string = $this->getCategoriesUlList();

      if  ( MODE_VENTE_PRIVEE == 'false' || (MODE_VENTE_PRIVEE == 'true' && $CLICSHOPPING_Customer->isLoggedOn())) {

        $header_template = '<!-- header slimemenu start -->' . "\n";

        ob_start();
        require_once($CLICSHOPPING_Template->getTemplateModules($this->group . '/content/header_template_slimmenu_master'));
        $header_template .= ob_get_clean();

        $header_template .= '<!-- header slimmenu end -->' . "\n";

        $CLICSHOPPING_Template->addBlock($header_template, $this->group);
      }
    }

    public function isEnabled() {
      return $this->enabled;
    }

    public function check() {
      return defined('MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_STATUS');
    }

    public function install() {
      $CLICSHOPPING_Db = Registry::get('Db');

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Do you want to enable this module ?',
          'configuration_key' => 'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_STATUS',
          'configuration_value' => 'True',
          'configuration_description' => 'Do you want to enable this module in your shop ?',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Please indicate the width of the content',
          'configuration_key' => 'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_CONTENT_WIDTH',
          'configuration_value' => '12',
          'configuration_description' => 'Please specify a display width',
          'configuration_group_id' => '6',
          'sort_order' => '2',
          'set_function' => 'clic_cfg_set_content_module_width_pull_down',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Souhaitez vous afficher des liens statiques ?',
          'configuration_key' => 'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LINK',
          'configuration_value' => 'True',
          'configuration_description' => 'Affiche des liens statiques <br /><br /><strong>Note :</strong><br /><br />- Attention au nombre de cat&eacutegories<br />- Nouveaut&eacutes<br /> - Promotions<br /> - Coups de coeur<br /> - Blog<br />- Contact-Nous',
          'configuration_group_id' => '6',
          'sort_order' => '2',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Veuillez indiquer le niveau de profondeur d\'affichage de la catégorie',
          'configuration_key' => 'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LEVEL',
          'configuration_value' => '12',
          'configuration_description' => 'Affiche un certains nombre de catégorie dans le menu<br /><br /><strong><u>Note :</u></strong><br /><br />- 0 pour illimité<br />- 2 pour afficher deux niveaux par exemple',
          'configuration_group_id' => '6',
          'sort_order' => '6',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );


      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Sort order',
          'configuration_key' => 'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_SORT_ORDER',
          'configuration_value' => '10',
          'configuration_description' => 'Sort order of display. Lowest is displayed first. The sort order must be different on every module',
          'configuration_group_id' => '6',
          'sort_order' => '6',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Please indicate where boxing should be displayed',
          'configuration_key' => 'MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_DISPLAY_PAGES',
          'configuration_value' => 'all',
          'configuration_description' => 'Sélectionnez les pages o&ugrave; la boxe doit être présente',
          'configuration_group_id' => '6',
          'sort_order' => '7',
          'set_function' => 'clic_cfg_set_select_pages_list',
          'date_added' => 'now()'
        ]
      );


      return $CLICSHOPPING_Db->save('configuration', ['configuration_value' => '1'],
        ['configuration_key' => 'WEBSITE_MODULE_INSTALLED']
      );

    }

    public function remove() {
      return Registry::get('Db')->exec('delete from :table_configuration where configuration_key in ("' . implode('", "', $this->keys()) . '")');
    }

    public function keys() {
      return ['MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_STATUS',
              'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_CONTENT_WIDTH',
               'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LINK',
               'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_LEVEL',
               'MODULES_HEADER_TEMPLATE_SLIMMENU_MASTER_SORT_ORDER',
               'MODULE_HEADER_TEMPLATE_SLIMMENU_MASTER_DISPLAY_PAGES'
             ];
    }
  }

<?php

  function as_product_dialog($product)
  {
    $html = '<div class="box box-widget widget-user-2">';

      $html .= '<div class="widget-user-header bg-gray">';

          $html .= '<div class="widget-user-image">';
            $html .= '<img class="img-circle" src="'.as_path_html('./as-media/' . $product['icon']).'" alt="User Avatar">';
          $html .= '</div>';

          $html .= '<span class="label label-info pull-right">';
            $html .= '<h5>QTY</h5><br>';
            $html .= '<span style="font-size: 25px">'.$product['actual']. '</span>';
          $html .= '</span>';

          $html .= '<h3 class="widget-user-username">'.$product['category'].' - '.$product['itemcode'].'</h3>';
          $html .= '<h5 class="widget-user-desc">'.$product['content'].'</h5>';
        $html .= '</div>';

        $html .= '<div class="box-footer no-padding">';

          $html .= '<ul class="nav nav-stacked">';
            $html .= '<li><a href="#">Volume <span class="pull-right">'.$product['volume'].'</span></a></li>';
            $html .= '<li><a href="#">Texture <span class="pull-right">'.$product['texture'].'</span></a></li>';
            $html .= '<li><a href="#">Mass <span class="pull-right">'.$product['mass'].'</span></a></li>';
          $html .= '</ul>';

        $html .= '</div>';

    $html .= '</div>';

    return $html;
  }

  function as_stock_add_form($elem, $product, $showcancel = true) 
  {
    $available_stock = (isset($product['available']) ? $product['available'] : 0);
    $html = '<form class="form-horizontal" method="post">';
    $html = '<div class="box-body">';

    $html .= '<input type="hidden" id="' . $elem . '_oldstock_'.$product['postid'].'" value="'.$available_stock.'" />';
    $html .= '<input type="hidden" id="' . $elem . '_newstock_'.$product['postid'].'" value="0" />';

    $html .= '<div class="form-group">
      <label class="col-sm-3 control-label">Quantity</label>
      <div class="col-sm-9">
      <input onkeyup="get_available_stock(\'' . $elem . '\', \''.$product['postid'].'\');" id="' . $elem . '_quantity_'.$product['postid'].'" type="number" placeholder="1" min="1" class="form-control" required />
      </div>
      </div>';

    $html .= '<div class="row">
      <div class="col-lg-6">
      <div class="input-group">
      <label>Condition</label>
      <select class="form-control" id="' . $elem . '_state_'.$product['postid'].'" required>
      <option value="1"> New </option><option value="3"> Damaged </option><option value="4"> Reject </option>
      </select>
      </div>
      </div>

      <div class="col-lg-6">
      <div class="input-group">
      <label>Type of Stock</label>
      <select class="form-control" id="' . $elem . '_type_'.$product['postid'].'" required>
      <option value="CSTOCK"> Commercial Stock </option><option value="IHSTOCK"> In-House Stock </option>
      </select>
      </div>
      </div>
      </div>';

    $html .= '<br>
      <div class="row">
      <div class="col-lg-6">
      <div class="input-group">
      <label>Buying Price</label>
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_bprice_'.$product['postid'].'" required />
      </div>
      </div>
      <div class="col-lg-6">
      <div class="input-group">
      <label>Selling Price</label>
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_sprice_'.$product['postid'].'" required />
      </div>
      </div>
      </div>';

    $html .= '</div>';

    $html .= '<div class="box-footer">';
    $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Submit This Entry" onclick="as_show_waiting_after(this, false); return as_add_stock(\'' . $elem . '\', '.$product['postid'].');" />';

    if ($showcancel)
    $html .= '<input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form(\'' . $elem . '_'.$product['postid'].'\');" />';

    $html .= '</div></form>';

    return $html;
  }

  function as_stock_history($history, $available_stock)
  {
    $html = '<div class="box-body">';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table no-margin">';
    $html .= '<thead><tr>';
    $html .= '<th>Entry</th><th>Type</th><th>Buy.Price</th><th>Sale.Price</th><th>Qty</th><th>Entry Date</th>';
    $html .= '</tr></thead>';

    $html .= '<tbody>';
    foreach($history as $entry) 
    {   
      $html .= '<tr>';
      $html .= '<td>'.$entry['activityid'].'#</td>';
      $html .= '<td>'.$entry['type'].'</td>';
      $html .= '<td>'.$entry['bprice'].'</td>';
      $html .= '<td>'.$entry['sprice'].'</td>';
      $html .= '<td>'.$entry['quantity'].'</td>';
      $html .= '<td>'.as_format_date($entry['created'], true).'</td>';
      $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '<tfoot><tr>';
    $html .= '<th></th><th colspan="3">AVAILABLE STOCK</th><th>'.$available_stock.'</th><th></th>';
    $html .= '</tr></tfoot>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  function as_new_customer_form($business) 
  {
    $html = '<form class="form-horizontal" method="post">';
    $html .= '<div class="box-body">';
    $html .= '<input type="hidden" id="business" value="'.$business.'" />';

    $html .= '<div class="form-group">';
    $html .= '<label class="col-sm-3 control-label">Full Name</label>';
    $html .= '<div class="col-sm-9">';
    $html .= '<input type="text" class="form-control" id="title" required />';
    $html .= '</div></div>';

    $html .= '<div class="row">
      <div class="col-lg-6">
      <div class="input-group">
      <label>Type</label>
      <select class="form-control" id="type" required>
      <option value="BUSINESS"> Business Customer </option><option value="INDIVIDUAL"> Individual Customer </option>
      </select>
      </div>
      </div>

      <div class="col-lg-6">
      <div class="input-group">
      <label>ID/BS Number (Optional)</label>
      <input type="text" class="form-control" id="idnumber" required />
      </div>
      </div>
      </div><br>';

    $html .= '<h4>CUSTOMER CONTACTS</h4><div class="row">
      <div class="col-lg-6">
      <div class="input-group">
      <label>Mobile Phone</label>
      <input type="text" class="form-control" id="mobile" required>
      </div>
      </div>

      <div class="col-lg-6">
      <div class="input-group">
      <label>Email Address (Optional)</label>
      <input type="text" class="form-control" id="email">
      </div>
      </div>
      </div><br>';                        

    $html .= '<h4>CUSTOMER LOCATION</h4>
    <div class="row">
    <div class="col-lg-6">
    <div class="input-group">
    <label>Region/location</label>
    <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d63820.55656798843!2d36.7706112!3d-1.3041664!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2ske!4v1574700166508!5m2!1sen!2ske" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen=""></iframe>
      </div>
      </div>';

    $htmlss = '<h4>CUSTOMER LOCATION</h4>
      <div class="row">
      <div class="col-lg-6">
      <div class="input-group">
      <label>Region/location</label>
      <input type="text" class="form-control" id="region" required>
      </div>
      </div>

      <div class="col-lg-6">
      <div class="input-group">
      <label>City/Town</label>
      <input type="text" class="form-control" id="city" required>
      </div>
      </div>
      </div>';

    $htmla = '<br><div class="row">
      <div class="form-group">
      <label class="col-sm-3 control-label">Street/Road</label>
      <div class="col-sm-8">
      <input type="text" class="form-control" id="road" required>
      </div>
      </div>
      </div>';

    $html .= '</div>';

    $html .= '<div class="box-footer">';
    $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Register This Customer" onclick="as_show_waiting_after(this, false); return as_new_customer();"/>';

    $html .= '</div></form>';
    return $html;
  }

  function as_products_search($businessid, $products, $type = 'inline')
  {
    $html .= '<ul class="products-list">';
    
    foreach ($products as $product) 
    {
      $texture = explode(';', $product['texture']);
      $product['actual_stock'] = (isset($product['actual']) ? $product['actual'] : 0);
      $product['available_stock'] = (isset($product['available']) ? $product['available'] : 0);

      if ($type == 'outline')
        $html .= "\n".'<li class="item list-item-result" alt="Click to Proceed with item Order" onclick="as_show_order_form(\'item_'.$product['postid'].'\')">';
      else
        $html .= "\n".'<li class="item list-item-result" alt="Click to Proceed with Stock Entry" onclick="as_show_quick_form(\'item_'.$product['postid'].'\')">';

      $html .= '<div class="product-img">'.as_get_media_html($product['icon'], 200, 200).'</div>';
      $html .= '<div class="product-info">';
      $html .= '<span class="product-title" style="font-size: 20px;">';
      $html .= '<span style="color: #006400;">'.$product['category'] . (isset($product['parentcat']) ? ' ' .$product['parentcat'] : ''). '</span>';
      $html .= ' - <span style="color: #f00;">' . $product['itemcode']. '</span></span>';
      
      $html .= '<table style="width:100%;"><tr><td>';
      $html .= '<span class="product-description" style="color: #000; width:275px;"><span style="font-size: 18px;"><b>'.$product['title'].':</b></span> ';
      if ($product['content'] != '') $html .= '<span style="color: #151B8D; font-size: 18px;">' . $product['content'] . '</span>';
      $html .= '<table><tr><td><b>Volume</b></td><td><b> : </b></td><td> ' .$product['volume'].'</td></tr>';
      $html .= '<tr><td><b>Mass</b></td><td><b> : </b></td><td> ' . $product['mass'].'</td></tr>';
      $html .= '<tr><td><b>Texture</b></td><td><b> / </b></td><td> <b>Color: '.$texture[0].' </b>; <span style="color: #151B8D; font-weight: bold;">Pattern: ' . $texture[1].'</span></td></tr>';
      $html .= "\n</table></span></td><td>";
    
      $html .= "\n".'<table><tr><td><span class="label label-info pull-right" style="width: 100px;"><b>ACTUAL</b><br><span id="get_actual_'.$product['postid'].'" style="font-size: 22px">'.$product['actual_stock']. '</span></span><br></td></tr>';
      $html .= '<tr><td><span class="label label-warning pull-right" style="width: 100px;"><b>AVAILABLE</b><br><span id="get_available_'.$product['postid'].'" style="font-size: 22px">'.$product['available_stock']. '</span></span></td></tr></table>';
      
      $html .= '</td></tr></table>';
      $html .= "</li>\n";
      
      if ($type == 'inline') 
      {
        $html .= "\n".'<li id="item_'.$product['postid'].'" style="display:none;">';	
        $html .= "\n".'<div id="get_itemresults_'.$product['postid'].'">';
  
        $html .= "\n".'<div class="nav-tabs-custom">';
      
        $html .= "\n".'<ul class="nav nav-tabs pull-right">';
        $html .= "\n".'<li class="active"><a href="#stock-entry'.$product['postid'].'" data-toggle="tab">RECEIVE</a></li>';
        $html .= "\n".'<li><a href="#stock-history'.$product['postid'].'" data-toggle="tab">HISTORY</a></li>';
        $html .= "\n".'<li class="pull-left header"><i class="fa fa-info"></i>STOCK ACTIONS</li>';
        $html .= "\n</ul>";
      
        $html .= "\n".'<div class="tab-content no-padding">';
      
        $html .= "\n".'<div class="tab-pane active" id="stock-entry'.$product['postid'].'" style="position: relative;">';	
        $html .= as_stock_add_form('get', $product);
        $html .= "\n</div>";
      
        $html .= "\n".'<div class="tab-pane" id="stock-history'.$product['postid'].'" style="position: relative;">';
        
        $stockids = as_db_find_by_stockitem($product['postid'], $businessid);
        if (count($stockids))
        {
          $history = as_db_select_with_pending( as_db_product_stock_activity($stockids[0]));
          $html .= as_stock_history($history, $product['available']);
        }
        else
        {
          $html .= "\n".'<h3>No Stock History for this product at the moment</h3>';
        }
      
        $html .= "\n</div>";
  
        $html .= "\n</div>";
        $html .= "\n</li>";
      }
    }
    
    $html .= "\n</ul>";
    return $html;
  }

  function as_business_customers_search($businessid)
  {    
    $html = '<div class="form-group" id="searchdiv">';
    $html .= '<div class="col-sm-12">';
    $html .= '<label for="searchcustomer">Search for a Customer</label>';
    $html .= '<input id="businessid" type="hidden" value="' . $businessid . '"/>';
    $html .= '<input id="searchcustomer" autocomplete="off" onkeyup="as_search_customer_change(this.value);" type="text" value="" class="form-control"/>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<input type="hidden" id="selected_customer" >';
    $html .= '<div id="search_customer_results"></div>';
    return $html;
  }

  function as_business_items_search($identifier, $type, $isdepartment = false)
  {    
    $html = '<div class="form-group" id="searchdiv">';
    $html .= '<div class="col-sm-12">';
    $html .= '<label for="search_item">Search for an Item by its Title, Code, Category or Description</label>';
    $html .= '<input id="search_item" autocomplete="off" onkeyup="as_search_item_change(\''.$type.'\');" onkeydown="as_search_item_change(\''.$type.'\');" type="text" value="" class="form-control">';
    $html .= '<input id="business_id" type="hidden" value="' . $identifier . '">';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<input type="hidden" id="selected_item" >';
    $html .= '<div class="form-group" id="results">';
    $html .= '<div class="col-sm-12x">';
    $html .= '<div id="item_results"></div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;    
  }

  function as_business_managers_search($businessid)
  {    
    $html = '<div class="form-group" id="searchdiv">';
    $html .= '<div class="col-sm-12">';
    $html .= '<label for="searchuser">Search by a User\'s Name, or Email Address</label>';
    $html .= '<input id="businessid" type="hidden" value="' . $businessid . '"/>';
    $html .= '<input id="searchuser" autocomplete="off" onkeyup="as_search_user_change(this.value);" onkeydown="as_search_user_change(this.value);" type="text" value="" class="form-control"/>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div id="feeback_user_results"></div>';
    $html .= '<div id="search_user_results"></div>';
    return $html;
  }

  function as_order_form() 
  {
    $html = '<form class="form-horizontal" method="post">';
    
    $html .= '<input type="hidden" id="stockid" />';
    $html .= '<input type="hidden" id="actual_stock" />';
    $html .= '<input type="hidden" id="available_stock" />';

    $html .= '<div class="row"><div class="form-group">
      <label class="col-sm-3 control-label">Quantity</label>
      <div class="col-sm-9">
      <input type="number" placeholder="1" min="1" class="form-control" id="quantity" required />
      </div></div></div><br>';

    $html .= '<div class="box-footer">';
    $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Submit" onclick="as_show_waiting_after(this, false); return as_submit_order();" />';
    $html .= '</div></form>';

    return $html;
  }

  function as_business_managers_list($userid, $businessid, $owners, $managers)
  {
    $owner = as_db_select_with_pending(as_db_user_profile($userid));
    
    $html = '<div id="list_results">';
    $html .= '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';

    $html .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
    $html .= '<div class="product-info"><span class="product-title" style="font-size: 20px;">';
    $html .= $owner['firstname'].' '.$owner['lastname'].'</span><span class="product-description">BUSINESS OWNER</span>';
    $html .= "</div><br></li>\n";

    if (count($managers)) {
      foreach ($managers as $mid) {
        if (!empty($mid) && $userid != $mid) {
          $manager = as_db_select_with_pending(as_db_user_profile($mid));
          $html .= '<li class="item list-item-result" onclick="as_show_quick_form(\'manlist_'.$manager['userid'].'\')">';
          $html .= '<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
          $html .= '<div class="product-info"><span class="product-title" style="font-size: 20px;">';
          $html .= $manager['firstname'].' '.$manager['lastname'].'</span><span class="product-description">BUSINESS MANAGER</span>';
          $html .= "</div><br></li>\n";
          
          $html .= '<li id="manlist_'.$manager['userid'].'" style="display:none;">';
          $html .= '<form class="form-horizontal"><div class="box-body">';
          
          $html .= '<div class="row">
                        <div class="col-lg-6">
                  <div class="form-group">
                  <label class="col-sm-3 control-label">Role</label>
                  <div class="col-sm-9">
                  <select class="form-control" id="manlist_role_'.$manager['userid'].'">
                  <option value="owners"> Owner </option>
                  <option value="managers"> Manager </option>
                  <option value="norole"> No Role </option>
                  </select>
                  </div>
                </div>
                        </div>
                        
                        <div class="col-lg-6">
                          <div class="input-group">
                  <input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Change this Role" onclick="return as_change_business_role(\'manlist\', '.$businessid.', '.$manager['userid'].');"/>
                  <input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form(\'manlist_'.$manager['userid'].'\');"/>
              </div>
            </div>
            </div>';
            
          $html .= '</div>';
          
          $html .= '</form>';
          $html .= "</li>\n";
        }
      }
    }

    $html .= '</ul>';
    $html .= '</div>';
    return $html;
  }

  function as_search_manager($identifier, $isdepartment = false)
  {    
    $html = '<div class="form-group" id="searchdiv">';
    $html .= '<div class="col-sm-12">';
    $html .= '<label for="searchuser">Search by a User\'s Name, or Email Address</label>';
    $html .= '<input id="searchuser" autocomplete="off" onkeyup="as_search_user_change();" type="text" class="form-control">';
    $html .= '<input id="department_id" type="hidden" value="' . $identifier . '">';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-group" id="managers_feedback">';
    $html .= '<div class="col-sm-12"><div id="manager_results"></div></div>';
    return $html;
  }

  function as_managers($identifier, $userid, $managers, $isdepartment = false)
  {
    $html = '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';
    $owner = as_db_select_with_pending(as_db_user_profile($userid));
    
    $html .= "\n\t".'<li class="item">';
    $html .= "\n\t\t".'<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
    $html .= "\n\t\t\t".'<div class="product-info">';
    $html .= "\n\t\t\t\t".'<a href="'.as_path_html('user/' . $owner['handle']).'" class="product-title" style="font-size: 20px;">';
    $html .= $owner['firstname'].' '.$owner['lastname']."</a>\n\t\t\t";
    $html .= "\n\t\t\t".'<span class="product-description">DEPARTMENT MANAGER</span>';
    $html .= "\n\t\t"."</div><br>";
    $html .= "\n\t</li>";

    if (count($managers)) {
        foreach ($managers as $mid) {
            if (!empty($mid) && $userid != $mid) {
                $manager = as_db_select_with_pending(as_db_user_profile($mid));
                $html .= "\n\t".'<li class="item">';
                $html .= "\n\t\t".'<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
                $html .= "\n\t\t\t".'<div class="product-info">';
                $html .= "\n\t\t\t\t".'<a href="'.as_path_html('user/' . $manager['handle']).'" class="product-title" style="font-size: 20px;">';
                $html .= $manager['firstname'].' '.$manager['lastname']."</a>\n\t\t\t";
                $html .= "\n\t\t\t".'<span class="product-description">DEPARTMENT MANAGER</span>';
                $html .= "\n\t\t"."</div><br>";
                $html .= "\n\t</li>";
            }
        }
    }

    $html .= '</ul>';
    return $html;
  }
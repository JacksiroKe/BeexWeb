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
    $html = '<form class="form-horizontal" method="post">';
    $html = '<div class="box-body">';

    $html .= '<input type="hidden" id="' . $elem . '_stockid_'.$product['postid'].'" value="'.$product['postid'].'">';
    $html .= '<input type="hidden" id="' . $elem . '_available_'.$product['postid'].'" value="'.$product['available'].'">';

    $html .= '<div class="form-group">
      <label class="col-sm-3 control-label">Quantity</label>
      <div class="col-sm-9">
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_qty_'.$product['postid'].'" required>
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
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_bprice_'.$product['postid'].'" required>
      </div>
      </div>
      <div class="col-lg-6">
      <div class="input-group">
      <label>Selling Price</label>
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_sprice_'.$product['postid'].'" required>
      </div>
      </div>
      </div>';

    $html .= '</div>';

    $html .= '<div class="box-footer">';
    $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Submit This Entry" onclick="as_show_waiting_after(this, false); return as_add_stock(\'' . $elem . '\', '.$product['postid'].');"/>';

    if ($showcancel)
    $html .= '<input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form(\'' . $elem . '_'.$product['postid'].'\');"/>';

    $html .= '</div></form>';

    return $html;
  }

  function as_stock_issue_form($elem, $product, $customers, $showcancel = true) 
  {
    $html = '<form class="form-horizontal" method="post">';
    $html = '<div class="box-body">';
    
    $html .= '<input type="hidden" id="' . $elem . '_stockid_'.$product['postid'].'" value="'.$product['postid'].'">';
    $html .= '<input type="hidden" id="' . $elem . '_actual_'.$product['postid'].'" value="'.$product['actual'].'">';
    $html .= '<input type="hidden" id="' . $elem . '_available_'.$product['postid'].'" value="'.$product['available'].'">';

    $html .= '<div class="row"><div class="form-group">
      <label class="col-sm-3 control-label">Quantity</label>
      <div class="col-sm-9">
      <input type="number" placeholder="1" min="1" class="form-control" id="' . $elem . '_qty_'.$product['postid'].'" required>
      </div></div></div><br>';

    $html .= '<div class="row"><div class="form-group">
      <label class="col-sm-3 control-label">Select Customer</label>
      <div class="col-sm-9">
      <select class="form-control" id="' . $elem . '_customer_'.$product['postid'].'" required>';
    
    if (count($customers)) {
      $p = 1;
      foreach ($customers as $customer) {
        $html .= '<option value="'.$customer['customerid'].'"> ' . $customer['title'] . ' </option>';
      }
    }
    $html .= '</select>
      </div></div></div><br>';

    $html .= '</div>';

    $html .= '<div class="box-footer">';
    $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Add to Shopping Catalogue" onclick="as_show_waiting_after(this, false); return as_add_shopping(\'' . $elem . '\', '.$product['postid'].');"/>';

    $html .= '<input type="submit" class="btn btn-success pull-right" style="margin-left: 10px"  value="Add to Shopping Catalogue and Confirm Order" onclick="as_show_waiting_after(this, false); return as_add_shopping_confirm(\'' . $elem . '\', '.$product['postid'].');"/>';

    $html .= '</div></form>';

    return $html;
  }

  function as_stock_history($history, $available_stock)
  {
    $html = '<div class="box-body">';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table no-margin">';
    $html .= '<thead><tr><th>Entry</th><th>Type</th><th>Buy.Price</th><th>Sale.Price</th><th>Qty</th><th>Entry Date</th></tr></thead>';

    $html .= '<tbody>';
    foreach($history as $entry) 
    {   
      $posted = as_when_to_html($entry['created'], 0);
      $entrydate = isset($entry['created']) ? $posted['data'] : '';
      $entryago = as_time_to_string(as_opt('db_time') - $entry['created']);

      $html .= '<tr>';
      $html .= '<td>'.$entry['activityid'].'#</td>';
      $html .= '<td>'.$entry['type'].'</td>';
      $html .= '<td>'.$entry['bprice'].'</td>';
      $html .= '<td>'.$entry['sprice'].'</td>';
      $html .= '<td>'.$entry['quantity'].'</td>';
      $html .= '<td>'.$entrydate . ' (' .$entryago . ' ago)</td>';
      $html .= '</tr>';
    }
    $html .= '<tfoot><tr><th></th><th colspan="3">AVAILABLE STOCK</th><th>'.$available_stock.'</th><th></th></tr></tfoot>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  function as_new_customer_form($business) 
  {
    $html = '<form class="form-horizontal" method="post">
    <div class="box-body">';
    $html .= '<input type="hidden" id="business" value="'.$business.'">';

    $html .= '<div class="form-group">
      <label class="col-sm-3 control-label">Full Name</label>
      <div class="col-sm-9">
      <input type="text" class="form-control" id="title" required>
      </div>
      </div>';

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
      <input type="text" class="form-control" id="idnumber" required>
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
      <label>Region/Locality</label>
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

    $html .= '<br><div class="row">
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

  function as_business_managers_search($businessid)
  {    
    $html = '<div class="form-group" id="searchdiv">';
    $html .= '<div class="col-sm-12">';
    $html .= '<label for="searchuser">Search by a User\'s Name, or Email Address</label>';
    $html .= '<input id="businessid" type="hidden" value="' . $businessid . '"/>';
    $html .= '<input id="searchuser" autocomplete="off" onkeyup="as_searchuser_change(this.value);" type="text" value="" class="form-control"/>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div id="feeback_results"></div>';
    $html .= '<div id="search_results"></div>';
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
    $html .= '<input id="searchuser" autocomplete="off" onkeyup="as_searchuser_change();" type="text" class="form-control">';
    $html .= '<input id="department_id" type="hidden" value="' . $identifier . '">';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-group" id="managers_feedback">';
    $html .= '<div class="col-sm-12"><div id="manager_results"></div></div>';
    return $html;
  }

  function as_search_items($identifier, $isdepartment = false)
  {
    
    $html = '<div class="form-group" id="searchdiv">
    <div class="col-sm-12">
    <label for="searchitem">Search by an Item\'s Title, Code, Category or Description</label>
    <input id="searchitem" autocomplete="off" onkeyup="as_searchitem_change();" onkeydown="as_searchitem_change();" type="text" value="" class="form-control">
    <input id="business_id" type="hidden" value="' . $identifier . '">
    </div>
    </div>
    <div class="form-group" id="results">
    <div class="col-sm-12">
    <div id="itemresults"></div>
    </div>';
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
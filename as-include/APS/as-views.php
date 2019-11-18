<?php

    function as_product_dialog($product)
    {
        $html = '<div class="box box-widget widget-user-2">';
        $html .= '<div class="widget-user-header bg-gray">';
        $html .= '<div class="widget-user-image">
            <img class="img-circle" src="'.as_path_html('./as-media/' . $product['icon']).'" alt="User Avatar">
          </div>';
        $html .= '<span class="label label-info pull-right"><h5>QTY</h5><span style="font-size: 25px">'.$product['stock']. '</span></span>';
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

    function as_stock_add_form($product, $showcancel = true) 
    {
        $html = '<form class="form-horizontal" method="post">
	  <div class="box-body">';
	  $html .= '<input type="hidden" id="stock_'.$product['postid'].'" value="'.$product['stock'].'">';
	$html .= '<div class="form-group">
		  <label class="col-sm-3 control-label">Quantity</label>
		  <div class="col-sm-9">
			<input type="number" placeholder="1" min="1" class="form-control" id="quantity_'.$product['postid'].'" required>
		  </div>
		</div>';

	$html .= '<div class="row">
                <div class="col-lg-6">
                  <div class="input-group">
				  	<label>Condition</label>
					<select class="form-control" id="condition_'.$product['postid'].'" required>
					<option value="1"> New </option><option value="3"> Damaged </option><option value="4"> Reject </option>
					</select>
                  </div>
                </div>
                
                <div class="col-lg-6">
                  <div class="input-group">
				  		<label>Type of Stock</label>
						<select class="form-control" id="type_'.$product['postid'].'" required>
						<option value="CSTOCK"> Commercial Stock </option><option value="IHSTOCK"> In-House Stock </option>
						</select>
                  </div>
                </div>
              </div>';
		
	$html .= '<br><div class="row">
			  <div class="col-lg-6">
				<div class="input-group">
					<label>Buying Price</label>
				  <input type="number" placeholder="1" min="1" type="text" class="form-control" id="bprice_'.$product['postid'].'" required>
				</div>
			  </div>
			  <div class="col-lg-6">
				<div class="input-group">
					<label>Selling Price</label>
				  <input type="number" placeholder="1" min="1" type="text" class="form-control" id="sprice_'.$product['postid'].'" required>
				</div>
			  </div>
			</div>';
			
	$html .= '</div>';
	
    $html .= '<div class="box-footer">';
     $html .= '<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Submit This Entry" onclick="as_show_waiting_after(this, false); return as_add_stock('.$product['postid'].');"/>';
     if ($showcancel)
     $html .= '<input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form('.$product['postid'].');"/>';
    
    $html .= '</div></form>';
        return $html;
    }

    function as_stock_history($history)
    {
        $html = '<div class="box-body">';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table no-margin">';
        $html .= '<thead><tr><th>Entry</th><th>Type</th><th>Buy.Price</th><th>Sale.Price</th><th>Quantity</th><th>Entry Date</th></tr></thead>';
        
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
                    /*<td>Call of Duty IV</td>
                    <td><span class="label label-success">Shipped</span></td>
                    <td>
                      <div class="sparkbar" data-color="#00a65a" data-height="20">90,80,90,-70,61,-83,63</div>
                    </td>*/
            $html .= '</tr>';
        }
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

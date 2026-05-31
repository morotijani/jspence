<?php

require_once("../db_connection/conn.php");

$output = '';
if (isset($_POST['gram-amount'])) {

	$gram = (isset($_POST['gram-amount']) ? sanitize($_POST['gram-amount']) : '');
	$volume = (isset($_POST['volume-amount']) ? sanitize($_POST['volume-amount']) : '');
	$current_price = (isset($_POST['current_price']) ? sanitize($_POST['current_price']) : '');
	$customer_name = (isset($_POST['customer_name']) ? sanitize($_POST['customer_name']) : '');
	$customer_contact = (isset($_POST['customer_contact']) ? sanitize($_POST['customer_contact']) : '');
	$pin = sanitize((int) $_POST['pin']);
	$note = (isset($_POST['note']) ? sanitize($_POST['note']) : '');
	$sale_type = ((admin_has_permission('supervisor')) ? 'in' : 'out');

	if ($pin == $admin_data['admin_pin']) {

		$runningCapital = find_capital_given_to($admin_id);
		if (is_array($runningCapital)) {
			$sale_daily = $runningCapital['daily_id'];

			$density = calculateDensity($gram, $volume);
			$pounds = calculatePounds($gram);
			$carat = calculateCarat($gram, $volume);
			$total_amount = calculateTotalAmount($gram, $volume, $current_price);

			$today_balance = _capital($admin_id)['today_balance'];
			$today_capital = _capital($admin_id)['today_capital'];
			$sale_id = guidv4();
			$createdAt = date("Y-m-d H:i:s");

			if (admin_has_permission('salesperson')) {
				if ($total_amount < 0) {
					$output = "There was a problem with the calculations";
				}

				if ($total_amount > $today_balance) {
					$output = "Today's remaining cash balance cannot complete this trade!";
				}
			}

			if (admin_has_permission('supervisor')) {
				$gb = remaining_gold_balance($admin_id);
				if ($gb <= 0) {
					$output = "Today's remaining gold balance cannot complete this trade!";
				}
			}

			if (empty($output) || $output == '') {
				$sql = "
					INSERT INTO `jspence_sales`(`sale_id`, `sale_gram`, `sale_volume`, `sale_density`, `sale_pounds`, `sale_carat`, `sale_price`, `sale_total_amount`, `sale_customer_name`, `sale_customer_contact`, `sale_comment`, `sale_type`, `sale_by`, `sale_daily`) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				";
				$statement = $conn->prepare($sql);
				$result = $statement->execute([$sale_id, $gram, $volume, $density, $pounds, $carat, $current_price, $total_amount, $customer_name, $customer_contact, $note, $sale_type, $admin_id, $sale_daily]);
				if (isset($result)) {
					$last_id = $conn->lastInsertId();

					$today = $runningCapital['daily_date'];
					$t = (admin_has_permission('supervisor') ? 'in' : 'out');
					$_t = (admin_has_permission('salesperson') ? 'exp' : '');

					$execute_data = [$today, $t, $_t, $admin_id, 0];
					$q = "
						SELECT 
							SUM(jspence_sales.sale_total_amount) AS ttsa, 
							CAST(jspence_sales.createdAt AS date) AS sd 
						FROM `jspence_sales` 
						WHERE CAST(jspence_sales.createdAt AS date) = ? 
						AND (jspence_sales.sale_type = ? OR jspence_sales.sale_type = ?)
						AND jspence_sales.sale_by = ? 
						AND jspence_sales.sale_status = ?
					";
					if (admin_has_permission('supervisor')) {
						$execute_data = [$last_id];
						$q = "
							SELECT sale_total_amount 
							FROM `jspence_sales` 
							WHERE id = ? 
							ORDER BY id DESC 
							LIMIT 1
						";
					}
					$statement = $conn->prepare($q);
					$statement->execute($execute_data);
					$r = $statement->fetchAll();

					$trade_status = 'bought'; // out-trade
					$today_total_balance = 0;
					if (admin_has_permission('salesperson')) {
						if ($r[0]['ttsa'] > 0) {
							$today_total_balance = (float) ($today_capital - $r[0]['ttsa']);
						}
					}

					$last_sale = 0;
					$pf = 0;
					if (admin_has_permission('supervisor')) {

						$trade_status = 'sold'; // in-trade

						$last_sale = $r[0]['sale_total_amount'];
						$today_total_balance = (float) ($today_balance - $last_sale);
						$today_total_balance = (($today_total_balance > 0) ? $today_total_balance : 0);

						$pf = (float) ($last_sale - $today_balance);
						$pf = (($pf > 0) ? $pf : 0);
					}

					update_today_capital_given_balance($trade_status, $today_capital, $today_total_balance, $pf, $last_sale, $today, $admin_id);

					$message = "added new sale with gram of " . $gram . " and volume of " . $volume . " and total amount of " . money($total_amount) . " and price of " . money($current_price) . " on id " . $sale_id . "";
					add_to_log($message, $admin_id);

					$createdAt = strtotime($createdAt);
					$arrayOutput = array('reference' => $sale_id, 'customername' => $customer_name, 'date' => $createdAt, 'gram' => $gram, 'volume' => $volume, 'density' => $density, 'pounds' => $pounds, 'carat' => $carat, 'total_amount' => $total_amount, 'current_price' => $current_price, 'by' => $admin_id, 'message' => '');
					$ouput = json_encode($arrayOutput);

					echo $ouput;
				} else {
					$output = 'Something went wrong.';
				}
			}
		} else {
			$output = "";
		}
	} else {
		$output = "Your PIN is invalid!";
	}
}

echo $output;

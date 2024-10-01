<?php 

// LIST AND SEARCH FOR PUSHES

require_once ("../db_connection/conn.php");

$today = date("Y-m-d");
$limit = 10;
$page = 1;

if ($_POST['page'] > 1) {
	$start = (($_POST['page'] - 1) * $limit);
	$page = $_POST['page'];
} else {
	$start = 0;
}

$where = '';
//if (!admin_has_permission()) {
    $where = ' AND push_to = "' . $admin_data["admin_id"] . '" AND push_from =  "' . $admin_data["admin_id"] . '" AND CAST(jspence_pushes.createdAt AS date) = "' . $today . '" ';
//}
$query = "
	SELECT *, jspence_pushes.id AS pid, jspence_pushes.createdAt AS pca, jspence_pushes.updatedAt AS sua, CAST(jspence_pushes.createdAt AS date) AS pdate 
    FROM jspence_pushes 
	INNER JOIN jspence_admin 
	ON (jspence_admin.admin_id = jspence_pushes.push_to AND jspence_admin.admin_id = jspence_pushes.push_from) 
	WHERE jspence_pushes.push_status = 0 
	$where 
";

$total_push = $conn->query("SELECT * FROM jspence_pushes INNER JOIN jspence_admin ON (jspence_admin.admin_id = jspence_pushes.push_from OR jspence_admin.admin_id = jspence_pushes.push_to) WHERE jspence_pushes.push_status = 0 $where")->rowCount();

$search_query = ((isset($_POST['query'])) ? sanitize($_POST['query']) : '');
$find_query = str_replace(' ', '%', $search_query);
if ($search_query != '') {
	$query .= '
		AND (push_id LIKE "%'.$find_query.'%" 
		OR push_amount LIKE "%'.$find_query.'%" 
		OR jspence_pushes.createdAt = "%'.$find_query.'%" 
		OR admin_fullname LIKE "%'.$find_query.'%") 
	';
} else {
	$query .= 'ORDER BY jspence_pushes.createdAt DESC ';
}

$filter_query = $query . 'LIMIT ' . $start . ', ' . $limit . '';

$total_data = $conn->query("SELECT * FROM jspence_pushes INNER JOIN jspence_admin ON (jspence_admin.admin_id = jspence_pushes.push_to AND jspence_admin.admin_id = jspence_pushes.push_from) WHERE jspence_pushes.push_status = 0 $where")->rowCount();

$statement = $conn->prepare($filter_query);
$statement->execute();
$result = $statement->fetchAll();
$count_filter = $statement->rowCount();

$output = ' 
        <div class="table-responsive mb-7">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Push ID</span></th>
                        <th>Capital ID</th>
                        <th>Amount</th>
                        <th>To</th>
                        <th>From</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
';

if ($total_data > 0) {
	$i = 1;
	foreach ($result as $row) {

        $_from = find_admin_with_id($row["push_from"]);
        $_to = find_admin_with_id($row["push_to"]);

        $option = ''; //////////
        if (!admin_has_permission() && $row["pdate"] == date("Y-m-d")) {
           $option = '
                <td class="text-end">
                    <a href="javascript:;" data-bs-target="#deleteModal_' . $row["pid"] . '" data-bs-toggle="modal" class="badge bg-light"> Reverse push </a>
                </td>
           '; 
        }

		$output .= '
            <tr>
                <td>' . $i . '</td>
                <td>' . $row["push_id"] . '</td>
                <td>' . $row["push_daily"] . '</td>
                <td>' . money($row["push_amount"]) .'</td>
                <td>' . ucwords($_from['admin_fullname']) . '</a></td>
                <td>' . ucwords($_to['admin_fullname']) . '</td>
                <td>'. pretty_date($row["pca"]) .'</td>
                ' . $option . '
            </tr>
		';
		$i++;
	}

} else {
	$output .= '
		<tr>
            <td colspan="6">
               <div class="alert alert-info"> No data found!</div>
            </td>
		</tr>
	';
}

$output .= '
			</tbody>
        </table>
    </div>
	<div class="row align-items-center">
        <div class="col">
            <!-- Text -->
            <p class="text-body-secondary mb-0">Showing ' . $count_filter . ' items out of ' . $total_data . ' results found</p>
        </div>
        <div class="col-auto">
';

if ($total_data > 0) {
	$output .= '
		<nav aria-label="Page navigation example">
            <ul class="pagination mb-0">
	';

	$total_links = ceil($total_data / $limit);

	$previous_link = '';
	$next_link = '';
	$page_link = '';

	if ($total_links > 4) {
		if ($page < 5) {
			for ($count = 1; $count <= 5; $count++) {
				$page_array[] = $count;
			}
			$page_array[] = '...';
			$page_array[] = $total_links;
		} else {
			$end_limit = $total_links - 5;
			if ($page > $end_limit) {
				$page_array[] = 1;
				$page_array[] = '...';

				for ($count = $end_limit; $count <= $total_links; $count++) {
					$page_array[] = $count;
				}
			} else {
				$page_array[] = 1;
				$page_array[] = '...';
				for ($count = $page - 1; $count <= $page + 1; $count++) {
					$page_array[] = $count;
				}
				$page_array[] = '...';
				$page_array[] = $total_links;
			}
		}
	} else {
		for ($count = 1; $count <= $total_links; $count++) {
			$page_array[] = $count;
		}
	}

	for ($count = 0; $count < count($page_array); $count++) {
		if ($page == $page_array[$count]) {
			$page_link .= '
				<li class="page-item active">
                    <a class="page-link" href="javascript:;">'.$page_array[$count].'</a>
                </li>
			';

			$previous_id = $page_array[$count] - 1;
			if ($previous_id > 0) {
				$previous_link = '
					<li class="page-item">
	                    <a class="page-link" href="javascript:;" data-page_number="'.$previous_id.'" aria-label="Previous">
	                        <span aria-hidden="true">&laquo;</span>
	                    </a>
	                </li>
				';
			} else {
				$previous_link = '
					<li class="page-item disabled">
	                    <a class="page-link" href="javascript:;" aria-label="Previous">
	                        <span aria-hidden="true">&laquo;</span>
	                    </a>
	                </li>
				';
			}

			$next_id = $page_array[$count] + 1;
			if ($next_id >= $total_links) {
				$next_link = '
					<li class="page-item disabled">
                        <a class="page-link" href="javascript:;" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
				';
			} else {
				$next_link = '
					<li class="page-item">
                        <a class="page-link" href="javascript:;" data-page_number="'.$next_id.' aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
				';
			}

		} else {
			
			if ($page_array[$count] == '...') {
				$page_link .= '
					<li class="page-item disabled">
						<a class="page-link" href="javascript:;">...</a>
					</li>
				';
			} else {
				$page_link .= '
					<li class="page-item">
						<a class="page-link page-link-go" href="javascript:;" data-page_number="'.$page_array[$count].'">'.$page_array[$count].'</a>
					</li>
				';
			}
		}

	}

	$output .= $previous_link. $page_link . $next_link;
}

echo $output . '	
                    </ul>
				</nav>
			</div>
		</div>
    ';
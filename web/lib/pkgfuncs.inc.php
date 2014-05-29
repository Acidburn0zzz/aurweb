<?php
include_once("config.inc.php");
include_once("pkgbasefuncs.inc.php");

/**
 * Determine if the user can delete a specific package comment
 *
 * Only the comment submitter, Trusted Users, and Developers can delete
 * comments. This function is used for the backend side of comment deletion.
 *
 * @param string $comment_id The comment ID in the database
 * @param string $atype The account type of the user trying to delete a comment
 * @param string|int $uid The user ID of the individual trying to delete a comment
 *
 * @return bool True if the user can delete the comment, otherwise false
 */
function can_delete_comment($comment_id=0, $atype="", $uid=0) {
	if (!$uid) {
		/* Unauthenticated users cannot delete anything. */
		return false;
	}
	if ($atype == "Trusted User" || $atype == "Developer") {
		/* TUs and developers can delete any comment. */
		return true;
	}

	$dbh = DB::connect();

	$q = "SELECT COUNT(*) FROM PackageComments ";
	$q.= "WHERE ID = " . intval($comment_id) . " AND UsersID = " . $uid;
	$result = $dbh->query($q);

	if (!$result) {
		return false;
	}

	$row = $result->fetch(PDO::FETCH_NUM);
	return ($row[0] > 0);
}

/**
 * Determine if the user can delete a specific package comment using an array
 *
 * Only the comment submitter, Trusted Users, and Developers can delete
 * comments. This function is used for the frontend side of comment deletion.
 *
 * @param array $comment All database information relating a specific comment
 * @param string $atype The account type of the user trying to delete a comment
 * @param string|int $uid The user ID of the individual trying to delete a comment
 *
 * @return bool True if the user can delete the comment, otherwise false
 */
function can_delete_comment_array($comment, $atype="", $uid=0) {
	if (!$uid) {
		/* Unauthenticated users cannot delete anything. */
		return false;
	} elseif ($atype == "Trusted User" || $atype == "Developer") {
		/* TUs and developers can delete any comment. */
		return true;
	} else if ($comment['UsersID'] == $uid) {
		/* Users can delete their own comments. */
		return true;
	}
	return false;
}

/**
 * Determine if the visitor can submit blacklisted packages.
 *
 * Only Trusted Users and Developers can delete blacklisted packages. Packages
 * are blacklisted if they are include in the official repositories.
 *
 * @param string $atype The account type of the user
 *
 * @return bool True if the user can submit blacklisted packages, otherwise false
 */
function can_submit_blacklisted($atype = "") {
	if ($atype == "Trusted User" || $atype == "Developer") {
		/* Only TUs and developers can submit blacklisted packages. */
		return true;
	}
	else {
		return false;
	}
}

/**
 * Check to see if the package name already exists in the database
 *
 * @param string $name The package name to check
 *
 * @return string|void Package name if it already exists
 */
function pkg_from_name($name="") {
	if (!$name) {return NULL;}
	$dbh = DB::connect();
	$q = "SELECT ID FROM Packages ";
	$q.= "WHERE Name = " . $dbh->quote($name);
	$result = $dbh->query($q);
	if (!$result) {
		return;
	}
	$row = $result->fetch(PDO::FETCH_NUM);
	return $row[0];
}

/**
 * Get licenses for a specific package
 *
 * @param int $pkgid The package to get licenses for
 *
 * @return array All licenses for the package
 */
function pkg_licenses($pkgid) {
	$lics = array();
	$pkgid = intval($pkgid);
	if ($pkgid > 0) {
		$dbh = DB::connect();
		$q = "SELECT l.Name FROM Licenses l ";
		$q.= "INNER JOIN PackageLicenses pl ON pl.LicenseID = l.ID ";
		$q.= "WHERE pl.PackageID = ". $pkgid;
		$result = $dbh->query($q);
		if (!$result) {
			return array();
		}
		while ($row = $result->fetch(PDO::FETCH_COLUMN, 0)) {
			$lics[] = $row;
		}
	}
	return $lics;
}

/**
 * Get package groups for a specific package
 *
 * @param int $pkgid The package to get groups for
 *
 * @return array All package groups for the package
 */
function pkg_groups($pkgid) {
	$grps = array();
	$pkgid = intval($pkgid);
	if ($pkgid > 0) {
		$dbh = DB::connect();
		$q = "SELECT g.Name FROM Groups g ";
		$q.= "INNER JOIN PackageGroups pg ON pg.GroupID = g.ID ";
		$q.= "WHERE pg.PackageID = ". $pkgid;
		$result = $dbh->query($q);
		if (!$result) {
			return array();
		}
		while ($row = $result->fetch(PDO::FETCH_COLUMN, 0)) {
			$grps[] = $row;
		}
	}
	return $grps;
}

/**
 * Get package dependencies for a specific package
 *
 * @param int $pkgid The package to get dependencies for
 *
 * @return array All package dependencies for the package
 */
function pkg_dependencies($pkgid) {
	$deps = array();
	$pkgid = intval($pkgid);
	if ($pkgid > 0) {
		$dbh = DB::connect();
		$q = "SELECT pd.DepName, dt.Name, pd.DepCondition, p.ID FROM PackageDepends pd ";
		$q.= "LEFT JOIN Packages p ON pd.DepName = p.Name ";
		$q.= "LEFT JOIN DependencyTypes dt ON dt.ID = pd.DepTypeID ";
		$q.= "WHERE pd.PackageID = ". $pkgid . " ";
		$q.= "ORDER BY pd.DepName";
		$result = $dbh->query($q);
		if (!$result) {
			return array();
		}
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$deps[] = $row;
		}
	}
	return $deps;
}

/**
 * Get package relations for a specific package
 *
 * @param int $pkgid The package to get relations for
 *
 * @return array All package relations for the package
 */
function pkg_relations($pkgid) {
	$rels = array();
	$pkgid = intval($pkgid);
	if ($pkgid > 0) {
		$dbh = DB::connect();
		$q = "SELECT pr.RelName, rt.Name, pr.RelCondition, p.ID FROM PackageRelations pr ";
		$q.= "LEFT JOIN Packages p ON pr.RelName = p.Name ";
		$q.= "LEFT JOIN RelationTypes rt ON rt.ID = pr.RelTypeID ";
		$q.= "WHERE pr.PackageID = ". $pkgid . " ";
		$q.= "ORDER BY pr.RelName";
		$result = $dbh->query($q);
		if (!$result) {
			return array();
		}
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$rels[] = $row;
		}
	}
	return $rels;
}

/**
 * Get the ID of a dependency type given its name
 *
 * @param string $name The name of the dependency type
 *
 * @return int The ID of the dependency type
 */
function pkg_dependency_type_id_from_name($name) {
	$dbh = DB::connect();
	$q = "SELECT ID FROM DependencyTypes WHERE Name = ";
	$q.= $dbh->quote($name);
	$result = $dbh->query($q);
	return $result->fetch(PDO::FETCH_COLUMN, 0);
}

/**
 * Get the ID of a relation type given its name
 *
 * @param string $name The name of the relation type
 *
 * @return int The ID of the relation type
 */
function pkg_relation_type_id_from_name($name) {
	$dbh = DB::connect();
	$q = "SELECT ID FROM RelationTypes WHERE Name = ";
	$q.= $dbh->quote($name);
	$result = $dbh->query($q);
	return $result->fetch(PDO::FETCH_COLUMN, 0);
}

/**
 * Get the HTML code to display a package dependency link
 *
 * @param string $name The name of the dependency
 * @param string $type The name of the dependency type
 * @param string $cond The package dependency condition string
 * @param int $pkg_id The package of the package to display the dependency for
 *
 * @return string The HTML code of the label to display
 */
function pkg_depend_link($name, $type, $cond, $pkg_id) {
	if ($type == 'optdepends' && strpos($name, ':') !== false) {
		$tokens = explode(':', $name, 2);
		$name = $tokens[0];
		$desc = $tokens[1];
	} else {
		$desc = '(unknown)';
	}

	$link = '<a href="';
	if (is_null($pkg_id)) {
		$link .= 'https://www.archlinux.org/packages/?q=' . urlencode($name);
	} else {
		$link .= htmlspecialchars(get_pkg_uri($name), ENT_QUOTES);
	}
	$link .= '" title="' . __('View packages details for') .' ' . htmlspecialchars($name) . '">';
	$link .= htmlspecialchars($name) . '</a>';
	$link .= htmlspecialchars($cond);

	if ($type == 'makedepends') {
		$link .= ' <em>(make)</em>';
	} elseif ($type == 'checkdepends') {
		$link .= ' <em>(check)</em>';
	} elseif ($type == 'optdepends') {
		$link .= ' <em>(optional) &ndash; ' . htmlspecialchars($desc) . ' </em>';
	}

	return $link;
}

/**
 * Determine packages that depend on a package
 *
 * @param string $name The package name for the dependency search
 *
 * @return array All packages that depend on the specified package name
 */
function pkg_required($name="") {
	$deps = array();
	if ($name != "") {
		$dbh = DB::connect();
		$q = "SELECT DISTINCT p.Name, PackageID FROM PackageDepends pd ";
		$q.= "JOIN Packages p ON pd.PackageID = p.ID ";
		$q.= "WHERE DepName = " . $dbh->quote($name) . " ";
		$q.= "ORDER BY p.Name";
		$result = $dbh->query($q);
		if (!$result) {return array();}
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$deps[] = $row;
		}
	}
	return $deps;
}

/**
 * Get all package sources for a specific package
 *
 * @param string $pkgid The package ID to get the sources for
 *
 * @return array All sources associated with a specific package
 */
function pkg_sources($pkgid) {
	$sources = array();
	$pkgid = intval($pkgid);
	if ($pkgid > 0) {
		$dbh = DB::connect();
		$q = "SELECT Source FROM PackageSources ";
		$q.= "WHERE PackageID = " . $pkgid;
		$q.= " ORDER BY Source";
		$result = $dbh->query($q);
		if (!$result) {
			return array();
		}
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$sources[] = $row[0];
		}
	}
	return $sources;
}

/**
 * Determine package names from package IDs
 *
 * @param string|array $pkgids The package IDs to get names for
 *
 * @return array|string All names if multiple package IDs, otherwise package name
 */
function pkg_name_from_id($pkgids) {
	if (is_array($pkgids)) {
		$pkgids = sanitize_ids($pkgids);
		$names = array();
		$dbh = DB::connect();
		$q = "SELECT Name FROM Packages WHERE ID IN (";
		$q.= implode(",", $pkgids) . ")";
		$result = $dbh->query($q);
		if ($result) {
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$names[] = $row['Name'];
			}
		}
		return $names;
	}
	elseif ($pkgids > 0) {
		$dbh = DB::connect();
		$q = "SELECT Name FROM Packages WHERE ID = " . $pkgids;
		$result = $dbh->query($q);
		if ($result) {
			$name = $result->fetch(PDO::FETCH_NUM);
		}
		return $name[0];
	}
	else {
		return NULL;
	}
}

/**
 * Determine if a package name is on the database blacklist
 *
 * @param string $name The package name to check
 *
 * @return bool True if the name is blacklisted, otherwise false
 */
function pkg_name_is_blacklisted($name) {
	$dbh = DB::connect();
	$q = "SELECT COUNT(*) FROM PackageBlacklist ";
	$q.= "WHERE Name = " . $dbh->quote($name);
	$result = $dbh->query($q);

	if (!$result) return false;
	return ($result->fetchColumn() > 0);
}

/**
 * Get the package details
 *
 * @param string $id The package ID to get description for
 *
 * @return array The package's details OR error message
 **/
function pkg_get_details($id=0) {
	$dbh = DB::connect();

	$q = "SELECT Packages.*, PackageBases.ID AS BaseID, ";
	$q.= "PackageBases.Name AS BaseName, PackageBases.CategoryID, ";
	$q.= "PackageBases.NumVotes, PackageBases.OutOfDateTS, ";
	$q.= "PackageBases.SubmittedTS, PackageBases.ModifiedTS, ";
	$q.= "PackageBases.SubmitterUID, PackageBases.MaintainerUID, ";
	$q.= "PackageBases.PackagerUID, PackageCategories.Category ";
	$q.= "FROM Packages, PackageBases, PackageCategories ";
	$q.= "WHERE PackageBases.ID = Packages.PackageBaseID ";
	$q.= "AND PackageBases.CategoryID = PackageCategories.ID ";
	$q.= "AND Packages.ID = " . intval($id);
	$result = $dbh->query($q);

	$row = array();

	if (!$result) {
		$row['error'] = __("Error retrieving package details.");
	}
	else {
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if (empty($row)) {
			$row['error'] = __("Package details could not be found.");
		}
	}

	return $row;
}

/**
 * Display the package details page
 *
 * @global string $AUR_LOCATION The AUR's URL used for notification e-mails
 * @global bool $USE_VIRTUAL_URLS True if using URL rewriting, otherwise false
 * @param string $id The package ID to get details page for
 * @param array $row Package details retrieved by pkg_get_details()
 * @param string $SID The session ID of the visitor
 *
 * @return void
 */
function pkg_display_details($id=0, $row, $SID="") {
	global $AUR_LOCATION;
	global $USE_VIRTUAL_URLS;

	$dbh = DB::connect();

	if (isset($row['error'])) {
		print "<p>" . $row['error'] . "</p>\n";
	}
	else {
		$base_id = pkgbase_from_pkgid($id);
		$pkgbase_name = pkgbase_name_from_id($base_id);

		include('pkg_details.php');

		if ($SID) {
			include('actions_form.php');
			include('pkg_comment_form.php');
		}

		$limit = isset($_GET['comments']) ? 0 : 10;
		$comments = pkgbase_comments($base_id, $limit);
		if (!empty($comments)) {
			include('pkg_comments.php');
		}
	}
}

/* pkg_search_page(SID)
 * outputs the body of search/search results page
 *
 * parameters:
 *  SID - current Session ID
 * preconditions:
 *  package search page has been accessed
 *  request variables have not been sanitized
 *
 *  request vars:
 *    O  - starting result number
 *    PP - number of search hits per page
 *    C  - package category ID number
 *    K  - package search string
 *    SO - search hit sort order:
 *          values: a - ascending
 *                  d - descending
 *    SB - sort search hits by:
 *          values: c - package category
 *                  n - package name
 *                  v - number of votes
 *                  m - maintainer username
 *    SeB- property that search string (K) represents
 *          values: n  - package name
 *                  nd - package name & description
 *                  x  - package name (exact match)
 *                  m  - package maintainer's username
 *                  s  - package submitter's username
 *    do_Orphans    - boolean. whether to search packages
 *                     without a maintainer
 *
 *
 *    These two are actually handled in packages.php.
 *
 *    IDs- integer array of ticked packages' IDs
 *    action - action to be taken on ticked packages
 *             values: do_Flag   - Flag out-of-date
 *                     do_UnFlag - Remove out-of-date flag
 *                     do_Adopt  - Adopt
 *                     do_Disown - Disown
 *                     do_Delete - Delete (requires confirm_Delete to be set)
 *                     do_Notify - Enable notification
 *                     do_UnNotify - Disable notification
 */
function pkg_search_page($SID="") {
	$dbh = DB::connect();

	/*
	 * Get commonly used variables.
	 * TODO: Reduce the number of database queries!
	 */
	if ($SID)
		$myuid = uid_from_sid($SID);
	$cats = pkgbase_categories($dbh);

	/* Sanitize paging variables. */
	if (isset($_GET['O'])) {
		$_GET['O'] = intval($_GET['O']);
		if ($_GET['O'] < 0)
			$_GET['O'] = 0;
	}
	else {
		$_GET['O'] = 0;
	}

	if (isset($_GET["PP"])) {
		$_GET["PP"] = intval($_GET["PP"]);
		if ($_GET["PP"] < 50)
			$_GET["PP"] = 50;
		else if ($_GET["PP"] > 250)
			$_GET["PP"] = 250;
	}
	else {
		$_GET["PP"] = 50;
	}

	/*
	 * FIXME: Pull out DB-related code. All of it! This one's worth a
	 * choco-chip cookie, one of those nice big soft ones.
	 */

	/* Build the package search query. */
	$q_select = "SELECT ";
	if ($SID) {
		$q_select .= "CommentNotify.UserID AS Notify,
			   PackageVotes.UsersID AS Voted, ";
	}
	$q_select .= "Users.Username AS Maintainer,
	PackageCategories.Category,
	Packages.Name, Packages.Version, Packages.Description,
	PackageBases.NumVotes, Packages.ID, Packages.PackageBaseID,
	PackageBases.OutOfDateTS ";

	$q_from = "FROM Packages
	LEFT JOIN PackageBases ON (PackageBases.ID = Packages.PackageBaseID)
	LEFT JOIN Users ON (PackageBases.MaintainerUID = Users.ID)
	LEFT JOIN PackageCategories
	ON (PackageBases.CategoryID = PackageCategories.ID) ";
	if ($SID) {
		/* This is not needed for the total row count query. */
		$q_from_extra = "LEFT JOIN PackageVotes
		ON (PackageBases.ID = PackageVotes.PackageBaseID AND PackageVotes.UsersID = $myuid)
		LEFT JOIN CommentNotify
		ON (PackageBases.ID = CommentNotify.PackageBaseID AND CommentNotify.UserID = $myuid) ";
	} else {
		$q_from_extra = "";
	}

	$q_where = "WHERE 1 = 1 ";
	/*
	 * TODO: Possibly do string matching on category to make request
	 * variable values more sensible.
	 */
	if (isset($_GET["C"]) && intval($_GET["C"])) {
		$q_where .= "AND PackageBases.CategoryID = ".intval($_GET["C"])." ";
	}

	if (isset($_GET['K'])) {
		if (isset($_GET["SeB"]) && $_GET["SeB"] == "m") {
			/* Search by maintainer. */
			$q_where .= "AND Users.Username = " . $dbh->quote($_GET['K']) . " ";
		}
		elseif (isset($_GET["SeB"]) && $_GET["SeB"] == "s") {
			/* Search by submitter. */
			$q_where .= "AND SubmitterUID = ".uid_from_username($_GET['K'])." ";
		}
		elseif (isset($_GET["SeB"]) && $_GET["SeB"] == "n") {
			/* Search by name. */
			$K = "%" . addcslashes($_GET['K'], '%_') . "%";
			$q_where .= "AND (Packages.Name LIKE " . $dbh->quote($K) . ") ";
		}
		elseif (isset($_GET["SeB"]) && $_GET["SeB"] == "b") {
			/* Search by package base name. */
			$K = "%" . addcslashes($_GET['K'], '%_') . "%";
			$q_where .= "AND (PackageBases.Name LIKE " . $dbh->quote($K) . ") ";
		}
		elseif (isset($_GET["SeB"]) && $_GET["SeB"] == "N") {
			/* Search by name (exact match). */
			$q_where .= "AND (Packages.Name = " . $dbh->quote($_GET['K']) . ") ";
		}
		elseif (isset($_GET["SeB"]) && $_GET["SeB"] == "B") {
			/* Search by package base name (exact match). */
			$q_where .= "AND (PackageBases.Name = " . $dbh->quote($_GET['K']) . ") ";
		}
		else {
			/* Search by name and description (default). */
			$K = "%" . addcslashes($_GET['K'], '%_') . "%";
			$q_where .= "AND (Packages.Name LIKE " . $dbh->quote($K) . " OR ";
			$q_where .= "Description LIKE " . $dbh->quote($K) . ") ";
		}
	}

	if (isset($_GET["do_Orphans"])) {
		$q_where .= "AND MaintainerUID IS NULL ";
	}

	if (isset($_GET['outdated'])) {
		if ($_GET['outdated'] == 'on') {
			$q_where .= "AND OutOfDateTS IS NOT NULL ";
		}
		elseif ($_GET['outdated'] == 'off') {
			$q_where .= "AND OutOfDateTS IS NULL ";
		}
	}

	$order = (isset($_GET["SO"]) && $_GET["SO"] == 'd') ? 'DESC' : 'ASC';

	$q_sort = "ORDER BY ";
	$sort_by = isset($_GET["SB"]) ? $_GET["SB"] : '';
	switch ($sort_by) {
	case 'c':
		$q_sort .= "CategoryID " . $order . ", ";
		break;
	case 'v':
		$q_sort .= "NumVotes " . $order . ", ";
		break;
	case 'w':
		if ($SID) {
			$q_sort .= "Voted " . $order . ", ";
		}
		break;
	case 'o':
		if ($SID) {
			$q_sort .= "Notify " . $order . ", ";
		}
		break;
	case 'm':
		$q_sort .= "Maintainer " . $order . ", ";
		break;
	case 'a':
		$q_sort .= "ModifiedTS " . $order . ", ";
		break;
	default:
		break;
	}
	$q_sort .= " Packages.Name " . $order . " ";

	$q_limit = "LIMIT ".$_GET["PP"]." OFFSET ".$_GET["O"];

	$q = $q_select . $q_from . $q_from_extra . $q_where . $q_sort . $q_limit;
	$q_total = "SELECT COUNT(*) " . $q_from . $q_where;

	$result = $dbh->query($q);
	$result_t = $dbh->query($q_total);
	if ($result_t) {
		$row = $result_t->fetch(PDO::FETCH_NUM);
		$total = $row[0];
	}
	else {
		$total = 0;
	}

	if ($result && $total > 0) {
		if (isset($_GET["SO"]) && $_GET["SO"] == "d"){
			$SO_next = "a";
		}
		else {
			$SO_next = "d";
		}
	}

	/* Calculate the results to use. */
	$first = $_GET['O'] + 1;

	/* Calculation of pagination links. */
	$per_page = ($_GET['PP'] > 0) ? $_GET['PP'] : 50;
	$current = ceil($first / $per_page);
	$pages = ceil($total / $per_page);
	$templ_pages = array();

	if ($current > 1) {
		$templ_pages['&laquo; ' . __('First')] = 0;
		$templ_pages['&lsaquo; ' . __('Previous')] = ($current - 2) * $per_page;
	}

	if ($current - 5 > 1)
		$templ_pages["..."] = false;

	for ($i = max($current - 5, 1); $i <= min($pages, $current + 5); $i++) {
		$templ_pages[$i] = ($i - 1) * $per_page;
	}

	if ($current + 5 < $pages)
		$templ_pages["... "] = false;

	if ($current < $pages) {
		$templ_pages[__('Next') . ' &rsaquo;'] = $current * $per_page;
		$templ_pages[__('Last') . ' &raquo;'] = ($pages - 1) * $per_page;
	}

	include('pkg_search_form.php');

	if ($result) {
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$searchresults[] = $row;
		}
	}

	include('pkg_search_results.php');

	return;
}

/**
 * Determine if a POST string has been sent by a visitor
 *
 * @param string $action String to check has been sent via POST
 *
 * @return bool True if the POST string was used, otherwise false
 */
function current_action($action) {
	return (isset($_POST['action']) && $_POST['action'] == $action) ||
		isset($_POST[$action]);
}

/**
 * Determine if sent IDs are valid integers
 *
 * @param array $ids IDs to validate
 *
 * @return array All sent IDs that are valid integers
 */
function sanitize_ids($ids) {
	$new_ids = array();
	foreach ($ids as $id) {
		$id = intval($id);
		if ($id > 0) {
			$new_ids[] = $id;
		}
	}
	return $new_ids;
}

/**
 * Get all package information in the database for a specific package
 *
 * @param string $pkgname The name of the package to get details for
 *
 * @return array All package details for a specific package
 */
function pkg_details_by_name($pkgname) {
	$dbh = DB::connect();
	$q = "SELECT Packages.*, PackageBases.Name AS BaseName, ";
	$q.= "PackageBases.CategoryID, PackageBases.NumVotes, ";
	$q.= "PackageBases.OutOfDateTS, PackageBases.SubmittedTS, ";
	$q.= "PackageBases.ModifiedTS, PackageBases.SubmitterUID, ";
	$q.= "PackageBases.MaintainerUID FROM Packages ";
	$q.= "INNER JOIN PackageBases ";
	$q.= "ON PackageBases.ID = Packages.PackageBaseID WHERE ";
	$q.= "Packages.Name = " . $dbh->quote($pkgname);
	$result = $dbh->query($q);
	if ($result) {
		$row = $result->fetch(PDO::FETCH_ASSOC);
	}
	return $row;
}

/**
 * Add package information to the database for a specific package
 *
 * @param int $base_id ID of the package base
 * @param string $pkgname Name of the new package
 * @param string $pkgver Version of the new package
 * @param string $pkgdesc Description of the new package
 * @param string $pkgurl Upstream URL for the new package
 *
 * @return int ID of the new package
 */
function pkg_create($base_id, $pkgname, $pkgver, $pkgdesc, $pkgurl) {
	$dbh = DB::connect();
	$q = sprintf("INSERT INTO Packages (PackageBaseID, Name, Version, " .
		"Description, URL) VALUES (%d, %s, %s, %s, %s)",
		$base_id, $dbh->quote($pkgname), $dbh->quote($pkgver),
		$dbh->quote($pkgdesc), $dbh->quote($pkgurl));
	$dbh->exec($q);
	return $dbh->lastInsertId();
}

/**
 * Add a dependency for a specific package to the database
 *
 * @param int $pkgid The package ID to add the dependency for
 * @param string $type The type of dependency to add
 * @param string $depname The name of the dependency to add
 * @param string $depcondition The  type of dependency for the package
 *
 * @return void
 */
function pkg_add_dep($pkgid, $type, $depname, $depcondition) {
	$dbh = DB::connect();
	$q = sprintf("INSERT INTO PackageDepends (PackageID, DepTypeID, DepName, DepCondition) VALUES (%d, %d, %s, %s)",
		$pkgid,
		pkg_dependency_type_id_from_name($type),
		$dbh->quote($depname),
		$dbh->quote($depcondition)
	);
	$dbh->exec($q);
}

/**
 * Add a relation for a specific package to the database
 *
 * @param int $pkgid The package ID to add the relation for
 * @param string $type The type of relation to add
 * @param string $relname The name of the relation to add
 * @param string $relcondition The version requirement of the relation
 *
 * @return void
 */
function pkg_add_rel($pkgid, $type, $relname, $relcondition) {
	$dbh = DB::connect();
	$q = sprintf("INSERT INTO PackageRelations (PackageID, RelTypeID, RelName, RelCondition) VALUES (%d, %d, %s, %s)",
		$pkgid,
		pkg_relation_type_id_from_name($type),
		$dbh->quote($relname),
		$dbh->quote($relcondition)
	);
	$dbh->exec($q);
}

/**
 * Add a source for a specific package to the database
 *
 * @param int $pkgid The package ID to add the source for
 * @param string $pkgsrc The package source to add to the database
 *
 * @return void
 */
function pkg_add_src($pkgid, $pkgsrc) {
	$dbh = DB::connect();
	$q = "INSERT INTO PackageSources (PackageID, Source) VALUES (";
	$q .= $pkgid . ", " . $dbh->quote($pkgsrc) . ")";

	$dbh->exec($q);
}

/**
 * Creates a new group and returns its ID
 *
 * If the groups already exists, the ID of the already existing group is
 * returned.
 *
 * @param string $name The name of the group to create
 *
 * @return int The ID of the group
 */
function pkg_create_group($name) {
	$dbh = DB::connect();
	$q = sprintf("SELECT ID FROM Groups WHERE Name = %s", $dbh->quote($name));
	$result = $dbh->query($q);
	if ($result) {
		$grpid = $result->fetch(PDO::FETCH_COLUMN, 0);
		if ($grpid > 0) {
			return $grpid;
		}
	}

	$q = sprintf("INSERT INTO Groups (Name) VALUES (%s)", $dbh->quote($name));
	$dbh->exec($q);
	return $dbh->lastInsertId();
}

/**
 * Add a package to a group
 *
 * @param int $pkgid The package ID of the package to add
 * @param int $grpid The group ID of the group to add the package to
 *
 * @return void
 */
function pkg_add_grp($pkgid, $grpid) {
	$dbh = DB::connect();
	$q = sprintf("INSERT INTO PackageGroups (PackageID, GroupID) VALUES (%d, %d)",
		$pkgid,
		$grpid
	);
	$dbh->exec($q);
}

/**
 * Creates a new license and returns its ID
 *
 * If the license already exists, the ID of the already existing license is
 * returned.
 *
 * @param string $name The name of the license to create
 *
 * @return int The ID of the license
 */
function pkg_create_license($name) {
	$dbh = DB::connect();
	$q = sprintf("SELECT ID FROM Licenses WHERE Name = %s", $dbh->quote($name));
	$result = $dbh->query($q);
	if ($result) {
		$licid = $result->fetch(PDO::FETCH_COLUMN, 0);
		if ($licid > 0) {
			return $licid;
		}
	}

	$q = sprintf("INSERT INTO Licenses (Name) VALUES (%s)", $dbh->quote($name));
	$dbh->exec($q);
	return $dbh->lastInsertId();
}

/**
 * Add a license to a package
 *
 * @param int $pkgid The package ID of the package
 * @param int $grpid The ID of the license to add
 *
 * @return void
 */
function pkg_add_lic($pkgid, $licid) {
	$dbh = DB::connect();
	$q = sprintf("INSERT INTO PackageLicenses (PackageID, LicenseID) VALUES (%d, %d)",
		$pkgid,
		$licid
	);
	$dbh->exec($q);
}

/**
 * Determine package information for latest package
 *
 * @param int $numpkgs Number of packages to get information on
 *
 * @return array $packages Package info for the specified number of recent packages
 */
function latest_pkgs($numpkgs) {
	$dbh = DB::connect();

	$q = "SELECT * FROM Packages ";
	$q.= "ORDER BY SubmittedTS DESC ";
	$q.= "LIMIT " .intval($numpkgs);
	$result = $dbh->query($q);

	if ($result) {
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$packages[] = $row;
		}
	}

	return $packages;
}

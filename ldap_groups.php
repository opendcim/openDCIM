<?php

function get_groups($user) {
	// Active Directory server
	$ldap_host = "ldap://citadel";

	// Active Directory DN, base path for our querying user
	$ldap_dn = "dc=home,dc=themillikens,dc=com";

	// Active Directory user for querying
	$query_user = "uid=svcacct,ou=people,dc=home,dc=themillikens,dc=com";
	$password = "ldapservice";

	// Connect to AD
	$ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_bind($ldap,$query_user,$password) or die("Could not bind to LDAP");

	// Search AD
	$results = ldap_search($ldap,$ldap_dn,"(uid=$user)",array("memberof","primarygroupid"));
	$entries = ldap_get_entries($ldap, $results);

	// No information found, bad user
	if($entries['count'] == 0) return false;

	// Get groups and primary group token
	$output = $entries[0]['memberof'];

	// Remove extraneous first entry
	array_shift($output);

	return $output;
}

print_r(get_groups('dcim'));
print_r(get_groups('scott'));

?>

#!/bin/bash

# DLMHelper.sh - provides useful functions related to creating and testing Synology DLM modules

create_flag=
pack_flag=
unpack_flag=
target=
name=

#
# Outputs a status based on the error string. If the error string is null/empty
# then SUCCESS is output, otherwise FAILURE ($error_string) is output.
#
# @param error_string
#
function output_status() {
	if [[ $# -eq 0 ]]; then
		echo -e "\033[1;32mSUCCESS\033[0m"
	else
		echo -e "\033[1;31mFAILURE\033[0m - $@"
	fi
}

#
# Parses the user arguments, setting the appropriate global flags
#
# @param $@ - parameters
#
function parse_args() {
	if [[ $# -eq 0 ]]; then
		return 1
	fi
	while [[ $# -gt 0 ]]
	do
		case "$1" in
			-c|--create)
				create_flag=1
				;;
			-n|--name)
				shift
				name="$1"
				;;
			-p|--pack)
				pack_flag=1
				shift
				target="$1"
				;;
			-u|--unpack)
				unpack_flag=1
				shift
				target="$1"
				;;
			*)
				echo "Invalid option: '$1'"
				return 1
				;;
		esac
		shift
	done
	return 0
}

#
# Displays the usage instructions to the user
#
function display_usage() {
	echo "Usage: DLMHelper.sh [-c] [-pu target] [-n name]"
	echo "	-c, --create:	Enters an interactive mode to create template DLM files"
	echo "			Target directory can be specified with -n"
	echo "	-n, --name:	Optional name used with -c, -u, -p"
	echo "	-p, --pack:	Creates DLM given target INFO file"
	echo "			DLM filename may be specified with -n (otherwise default name used)"
	echo "	-u, --unpack:	Unpacks target DLM"
	echo "			Destination directory may specified with -n (default current directory)"
	echo ""
	echo "	Examples:"
	echo ""
	echo "	Create a new template DLM:"
	echo "		./DLMHelper.sh --create"
	echo "	Create a new template DLM into directory 'newDLMdir':"
	echo "		./DLMHelper.sh -c -n newDLMdir"
	echo "	Unpack a DLM into the current directory:"
	echo "		./DLMHelper.sh --unpack bigmammajamma.dlm"
	echo "	Unpack a DLM into a new directory 'newdir':"
	echo "		./DLMHelper.sh -u bigmammajamma.dlm -n newdir"
	echo "	Pack DLM files using a default name (extracted from INFO file):"
	echo "		./DLMHelper.sh -p newdir/INFO"
	echo "	Pack DLM files into a DLM named 'bigmammajamma.dlm':"
	echo "		./DLMHelper.sh --pack newdir/INFO --name bigmammajamma.dlm"
	echo ""
}

#
# Creates template DLM files based on user preferences
#
function create() {

	# If the user enters this text, then this function will exit with code 2
	restart_response="<<restart>>"

	echo ""
	echo "Interactive DLM template creator - enter the information below."
	echo ""
	echo "	- At any time, use ^C to cancel the operation."
	echo "	- At any time, answer '$restart_response' to restart the process."
	echo ""
	
	# Clear the variables (necessary if restarting)
	module_name=''
	version=''
	class_name=''
	display_name=''
	description=''
	website=''
	queryurl=''
	format=''
	limit=''
	extra=''

	# Define an escape string
	escape='s/\\/\\\\/g;s/\$/\\\$/g;'"s/\'/\\\\'/g;"'s:/:\\/:g'

	# Get the user responses
	until [[ -n $module_name ]]; do
		read -p "Module name (hint: short, and not displayed to user) [MANDATORY]: " module_name
	done
	if [[ $module_name == "$restart_response" ]]; then
		return 2
	fi
	# Escape the string
	module_name="$(echo $module_name | sed $escape)"

	until [[ -n $version ]]; do
		read -p "Version # [MANDATORY]: " version
	done
	if [[ $version == "$restart_response" ]]; then
		return 2
	fi
	version="$(echo $version | sed $escape)"

	until [[ -n $class_name ]]; do
		read -p "PHP object class name (e.g. SynoDLMSearchMyWebsite) [MANDATORY]: " class_name
	done
	if [[ $class_name == "$restart_response" ]]; then
		return 2
	fi
	class_name="$(echo $class_name | sed $escape)"

	read -p "Display name (hint: shown to user, if blank $module_name is used) [RECOMMENDED]: " display_name
	if [[ $display_name == "$restart_response" ]]; then
		return 2
	fi
	display_name="$(echo $display_name | sed $escape)"

	read -p "Description (hint: shown to user) [RECOMMENDED]: " description
	if [[ $description == "$restart_response" ]]; then
		return 2
	fi
	description="$(echo $description | sed $escape)"

	until [[ -n $website ]]; do
		read -p "Domain name of search site (e.g. https://searchdomain.com) [MANDATORY]: " website
	done
	if [[ $website == "$restart_response" ]]; then
		return 2
	fi
	website="$(echo $website | sed $escape)"

	until [[ -n $queryurl ]]; do
		read -p "Path of query URL without domain (e.g. '/search.php?q=') [MANDATORY]: " queryurl
	done
	if [[ $queryurl == "$restart_response" ]]; then
		return 2
	fi
	queryurl="$(echo $queryurl | sed $escape)"

	echo "What is the format of the results returned from the website?"
	echo "	[1] - RSS"
	echo "	[2] - JSON"
	echo "	[3] - Other (i.e. manual parsing needed)"
	echo "	[4] - Not sure ... just leave all the template code and I'll decide later."

	regx_format_response="^[1-4]$"
	until [[ $format =~ $regx_format_response ]]; do
		read -p "Choose 1-4: " format
		if [[ $format == "$restart_response" ]]; then
			return 2
		fi
	done
	
	echo "Do you want to limit the results returned during parsing in the PHP file?"
	echo "	[0] - No"
	echo "	[X] - Yes, specify limit (i.e. number greater than 0)"

	regx_limit_response="^[0-9]+$"
	until [[ $limit =~ $regx_limit_response ]]; do
		read -p "Enter number: " limit
		if [[ $limit == "$restart_response" ]]; then
			return 2
		fi
	done

	echo "Will your PHP class require more than 1 query to get all torrent data? For example: a single query to get a list of all torrent names, but additional queries will be needed to get hash codes."
	echo "	[Y]es - I'll need more than 1 query."
	echo "	[N]o - Just a single query, thanks!"
	echo "	[D]on't know - Just leave all the template code and I'll decide later."

	regx_yes_no_other_response="^[yndYND].*"
	until [[ $extra =~ $regx_yes_no_other_response ]]; do
		read -p "'Y'es 'N'o or 'D'on't know: " extra
		if [[ $extra == "$restart_response" ]]; then
			return 2
		fi
	done
	extra=$(echo "$extra" | tr '[:lower:]' '[:upper:]')

	echo "Does the server require an account (i.e. a username/password)?"
	echo "	[Y]es - The username/password is entered in BT Search under the Edit button"
	echo "	[N]o  - A username/password is not required."

	# Define yes/no and just the yes response
	regx_yes_no="^[yn].*"
	regx_yes="^[y].*"
	# Compare the lowercase input ${var,,} to the yes/no regex
	until [[ ${USES_ACCOUNT,,} =~ $regx_yes_no ]]; do
		read -p "'Y'es 'N'o: " USES_ACCOUNT
		if [[ $USES_ACCOUNT == "$restart_response" ]]; then 
			return 2
		fi
	done
	if [[ ${USES_ACCOUNT,,} =~ $regex_yes ]]; then
		USES_ACCOUNT=true
	else 
		USES_ACCOUNT=false
	fi

	# Make the optional directory if desired
	if [[ -n $name && ! -e $name ]]; then
		echo
		echo "---- Creating DLM template files ----"
		echo -n "Creating '$name' directory. "
		error=$(mkdir $name 2>&1)
		output_status $error
	fi

	# Create the INFO and PHP files -- use the current directory if none specified
	if [[ -z $name ]]; then
		name='.'
	fi
	INFO_swap="s/_name_/$module_name/;s/_displayname_/$display_name/;s/_description_/$description/;s/_version_/$version/;s/_website_/$website/;s/_class_/$class_name/;s/_account_/$USES_ACCOUNT/"
	PHP_swap="s/_class_/$class_name/;s/_website_/$website/;s/_queryurl_/$queryurl/;s/_maxresults_/$limit/"
	
	echo -n "Creating $name/INFO file. " 
	error=$(sed "$INFO_swap" < templates/INFO > $name/INFO 2>&1)
	output_status $error
	error=$(sed "$PHP_swap" < templates/search.php > $name/search.tmp 2>&1)
	echo -n "Creating $name/search.tmp file. "
	output_status $error

	# Modify the file based on the user format preferences
	case "$format" in
		1) # RSS
			echo "User directed removal of JSON and MANUAL parsing code, keeping RSS code."
			PHP_modify="/\<\<JSON/,/\>\>/d;/\<\<MANUAL/,/\>\>/d;s/\<\<RSS://"
			;;
		2) # JSON
			echo "User directed removal of RSS and MANUAL parsing code, keeping JSON code."
			PHP_modify="/\<\<RSS/,/\>\>/d;/\<\<MANUAL/,/\>\>/d;s/\<\<JSON://"
			;;
		3) # Manual (i.e. other)
			echo "User directed removal of RSS and JSON parsing code, keeping MANUAL code."
			PHP_modify="/\<\<RSS/,/\>\>/d;/\<\<JSON/,/\>\>/d;s/\<\<MANUAL://"
			;;
		4) # Nothing 
			PHP_modify=""
			;;
	esac
	
	if [[ -n $PHP_modify ]]; then
		PHP_modify="$PHP_modify;"
	fi
	if [[ $extra == "N" ]]; then
		echo "User directed removal of EXTRA url query code (i.e. \"curl\" templates)"
		PHP_modify="$PHP_modify""/\<\<EXTRA/,/\>\>/d"
	else
		PHP_modify="$PHP_modify""s/\<\<EXTRA://"
	fi
	
	if [[ -n $PHP_modify ]]; then
		# Chain the modify string and delete the final ">>" in the file. This can't be
		# done in the PHP_modify or else the delete substitutions could delete
		# the entire file since all the blocks end with ">>".
		echo -n "Modifying template, saving results to $name/search.php. "
		error=$(sed "$PHP_modify" < $name/search.tmp | sed "s/\>\>//" > $name/search.php 2>&1)
		output_status $error
		echo -n "Removing search.tmp. "
		error=$(rm $name/search.tmp 2>&1)
		output_status $error
	else
		echo -n "Renaming $name/search.tmp to $name/search.php. "
		error=$(mv $name/search.tmp $name/search.php 2>&1)
		output_status $error
	fi

	echo "-------------------------------------"
	echo
	echo "Template DLM files created."
	echo "When finished editing, test the DLM by:"
	echo "	php DLMTester.php -s \"Search String\" $name/INFO" 
	echo
	echo "When finished testing, pack the DLM file by:"
	echo "	./DLMHelper.sh --pack $name/INFO --name Your_DLM_name.dlm"
	echo 

	return 0
}

#
# Packs a user's DLM
#
function pack() {
	# Make sure the DLM INFO file exists
	if [[ ! -e $target ]]; then
		echo "DLM INFO file '$target' does not exist."
		exit 1
	fi
	# Get the directory of the target file -- start by assuming current directory
	path='./'
	regx_path="^(.*\/)(.*)$"
	if [[ $target =~ $regx_path ]]; then
		path=${BASH_REMATCH[1]}
		target=${BASH_REMATCH[2]}
	fi
	# Make sure the DLM module file exists
	result="$(cat $path$target | grep module)"
	# Perform a regex match on the target filename
	regx_value="\":[[:space:]]*\"([^\"]*)\","
	if [[ $result =~ $regx_value ]]; then
		module=${BASH_REMATCH[1]}
		if [[ ! -e $path$module ]]; then
			echo "DLM module file '$module' specified in '$target' not found."
			exit 1
		fi
	else
		echo "DLM module file not found in '$path$target'. Is it formatted correctly?"
		exit 1
	fi
	# If no DLM destination name was specified, then search for it in the INFO file
	if [[ -z $name ]]; then
		result="$(cat $path$target | grep name)"
		if [[ $result =~ $regx_value ]]; then
			name=${BASH_REMATCH[1]}
		else
			echo "DLM name not found in '$target'. Is it formatted correctly?"
			exit 1
		fi
	fi
	
	# Pack the file
	tar czf $name -C $path $target $module
	if [[ $? -eq 0 ]]; then
		echo "Packed '$path$target' and '$path$module' in DLM file '$name'"
	else
		echo "Unable to pack '$path$target' and '$path$module' into DLM file '$name'"
	fi
}

#
# Unpacks a DLM into the current or new directory (if specified)
#
function unpack() {
	if [[ ! -e $target ]]; then 
		echo "$target does not exist."
		exit 1
	fi

	if [[ -n $name ]]; then
		if [[ ! -e $name ]]; then
			mkdir $name
		fi
		tar xzf $target -C $name
	else
		tar xzf $target
	fi

	if [[ $? -eq 0 ]]; then
		echo "$target unpacked to directory '$name'."
	else
		echo "Error unpacking $target to directory '$name.'"
	fi
}

###
#
# Script entry point
#
###
parse_args $@
if [[ $? -eq 1 ]]; then
	display_usage $0
	exit 1
fi

if [[ -n $create_flag ]]; then
	while true; do
		create
		if [[ $? -ne 2 ]]; then
			exit
		fi
	done
elif [[ -n $pack_flag ]]; then
	pack
elif [[ -n $unpack_flag ]]; then
	unpack
fi

exit 0

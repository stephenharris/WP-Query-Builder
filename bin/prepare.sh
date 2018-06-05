#!/bin/bash

# Exit if anything fails AND echo each command before executing
# http://www.peterbe.com/plog/set-ex
set -ex

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

# Set up constant
DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WORDPRESS_TEST_DIR=${WORDPRESS_TEST_DIR-/tmp/wordpress-tests-lib}
WORDPRESS_SITE_DIR=${WORDPRESS_SITE_DIR-/tmp/wordpress}

# Install database for WordPress
mysql --user="$DB_USER" --password="$DB_PASS" $EXTRA -e "DROP DATABASE IF EXISTS $DB_NAME";
mysqladmin --no-defaults create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA;

# Download WordPress
mkdir -p $WORDPRESS_SITE_DIR
vendor/bin/wp core download --force --version=$WP_VERSION --path=$WORDPRESS_SITE_DIR

# Create configs
rm -f ${WORDPRESS_SITE_DIR}wp-config.php
vendor/bin/wp core config --path=$WORDPRESS_SITE_DIR --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST

# We only run the install command so that we can run further wp-cli commands
vendor/bin/wp core install --path=$WORDPRESS_SITE_DIR --url="wordpress.dev" --title="wordpress.dev" --admin_user="admin" --admin_password="password" --admin_email="admin@wp.dev"

# Copy our test subject to the plugins directory
#rsync -av --exclude=".*" $TRAVIS_BUILD_DIR/build ${WORDPRESS_SITE_DIR}wp-content/plugins/

# Install test suite for phpunit and test configuration
vendor/bin/wp test-library download --path=$WORDPRESS_SITE_DIR --library-path=$WORDPRESS_TEST_DIR --force
vendor/bin/wp test-library config --path=$WORDPRESS_SITE_DIR --library-path=$WORDPRESS_TEST_DIR --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST --force

# Makefile for ZF2 packages
#
# Parameters you should pass in:
#
# - PHP=<path to php> (if you want to specify a specific PHP binary)
# - VERSION=<version string> (required for end-user/api docs, zf2 tarball, and
#   pyrus!)
#
# You can override any given parameter by passing it in the command line or
# defining an equivalent ENV variable via export.

VERSION?=false

PHP=php
COMPOSER=$(CURDIR)/composer.phar
PUBLIC=$(CURDIR)/../../public

.PHONY: clean

composer: composer-vendor
	@echo "Running satis to create composer repository..."
	-$(PHP) $(CURDIR)/bin/satis build $(CURDIR)/satis.json $(PUBLIC)
	@echo "[DONE] Running satis to create composer repository."

composer-vendor:
	@echo "Ensuring satis dependencies are installed..."
	@if [ ! -f "$(CURDIR)/vendor/autoload.php" ] ; then \
		echo "Installing composer dependencies for satis" ; \
		$(PHP) $(COMPOSER) install ; \
		if [ $? != 0 ] ; then \
		    echo "Composer installation failed" ; \
			exit 1 ; \
		fi ; \
	fi
	@echo "[DONE] Ensuring satis dependencies are installed."

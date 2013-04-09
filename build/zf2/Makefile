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
TAR=tar
ZIP=zip
UNZIP=unzip

COMPOSER=$(CURDIR)/composer.phar
PUBLIC=$(CURDIR)/../../public
PACKAGES=$(PUBLIC)/packages.json

EXPORT_HOME=$(CURDIR)/exports
RELEASE_HOME=$(PUBLIC)/releases
STAGE_HOME=$(CURDIR)/stage

.PHONY: clean

composer-vendor:
	@echo "Ensuring satis dependencies are installed..."
ifeq ($(wildcard vendor/autoload.php),)
	@echo "Installing composer dependencies for satis"
	-$(PHP) $(COMPOSER) install
	@if [ $$? -ne 0 ] ; then \
		echo "Composer installation failed" ; \
		exit 1 ; \
	fi
endif
	@echo "[DONE] Ensuring satis dependencies are installed."

composer: composer-vendor
	@echo "Running satis to create composer repository..."
	-$(PHP) $(CURDIR)/bin/satis build $(CURDIR)/satis.json $(PUBLIC)
	@echo "[DONE] Running satis to create composer repository."

version:
ifeq ("$(VERSION)", "false")
	@echo "Please pass the VERSION assignment for this target"
	exit 42
endif

zf2-export: version
	@echo "Exporting composer package for ZF version $(VERSION)..."
ifneq ($(wildcard $(EXPORT_HOME)/ZendFramework-$(VERSION)),)
	@echo "Composer package for ZF version $(VERSION) is already exported"
else
	$(eval ZIPPATH := $(shell $(PHP) "$(CURDIR)/bin/zf2-tarball-version.php" $(VERSION) $(PACKAGES)))
	@echo "Found and using composer package at $(ZIPPATH)"
	@$(UNZIP) -q -o -d $(EXPORT_HOME)/ZendFramework-$(VERSION) $(ZIPPATH)
endif
	@echo "[DONE] Exporting composer package for ZF version $(VERSION)."

zf2-dist: zf2-export
ifneq ($(wildcard $(STAGE_HOME)/ZendFramework-$(VERSION).tgz),)
	echo "Zend Framework $(VERSION) is already staged"
else
	@echo "Creating TGZ archive"
	-(cd $(EXPORT_HOME) && $(TAR) czf $(STAGE_HOME)/ZendFramework-$(VERSION).tgz ZendFramework-$(VERSION))
	@echo "Creating ZIP archive"
	-(cd $(EXPORT_HOME) && $(ZIP) -r -q $(STAGE_HOME)/ZendFramework-$(VERSION).zip ZendFramework-$(VERSION))
endif

zf2-apidoc: zf2-export

zf2-release: zf2-dist
	@echo "Releasing Zend Framework $(VERSION)..."
	$(eval RELEASE_PATH := "$(RELEASE_HOME)/ZendFramework-$(VERSION)")
ifneq ($(wildcard $(RELEASE_PATH)),)
	@echo "Zend Framework $(VERSION) has already been released"
else
	-mkdir $(RELEASE_PATH)
	-cp $(STAGE_HOME)/ZendFramework-$(VERSION).* "$(RELEASE_PATH)/"
endif
	@echo "[DONE] Releasing Zend Framework $(VERSION)."

clean:
	-rm -f $(STAGE_HOME)/*.zip
	-rm -f $(STAGE_HOME)/*.tgz
	-rm -Rf $(EXPORT_HOME)/Zend*
	-rm -Rf $(EXPORT_HOME)/Component*
	-rm -Rf $(EXPORT_HOME)/ZF*

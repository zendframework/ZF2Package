# Makefile for ZF1 packages
#
# Parameters you should pass in:
#
# - ZF_VERSION=<version string> (without this, the build will exit immediately!)
#
# You can override any given parameter by passing it in the command line or
# defining an equivalent ENV variable via export.

ZF_VERSION?=false

# Various commands
GIT=git
PHPDOC=phpdoc
PHP=php
RSYNC=rsync
TAR=tar
WGET=wget
ZIP=zip

CONFIGS=$(CURDIR)/config
DOWNLOADS=$(CURDIR)/downloads
SCRIPTS=$(CURDIR)/scripts
DOC_UTILS=$(CURDIR)/doc-utils

ZF_LANG=de en fr ja ru zh
ZF_LANG_EXTRAS=en

DOJO_URL=http://download.dojotoolkit.org/release-1.5.2/dojo-release-1.5.2-src.tar.gz
RELEASES_HOME=$(CURDIR)/../../public/releases
STAGE_HOME=$(CURDIR)/stage

ZF_ARCHIVE_URL=https://api.github.com/repos/zendframework
ZF_PRODUCT_NAME=Zend Framework
ZF_PRODUCTNAME=ZendFramework
ZF_PRODUCT=$(ZF_PRODUCTNAME)-$(ZF_VERSION)

ZF_EXPORT_DIR=$(CURDIR)/exports/$(ZF_PRODUCT)
ZF_STAGE_DIR=$(STAGE_HOME)/$(ZF_PRODUCT)
ZF_RELEASE_DIR=$(RELEASES_HOME)/$(ZF_PRODUCT)

ZF_MINIMAL=$(ZF_PRODUCT)-minimal
ZF_MINIMAL_EXPORT_DIR=$(CURDIR)/exports/$(ZF_MINIMAL)
ZF_MINIMAL_STAGE_DIR=$(STAGE_HOME)/$(ZF_MINIMAL)

ZF_ZIPBALL=$(ZF_PRODUCT).zip
ZF_TARBALL=$(ZF_PRODUCT).tar.gz
ZF_MINIMAL_ZIPBALL=$(ZF_MINIMAL).zip
ZF_MINIMAL_TARBALL=$(ZF_MINIMAL).tar.gz

API_DOC_OUTPUT_STYLE=zend
API_DOC_DEST_CORE=$(ZF_STAGE_DIR)/documentation/api/core
API_DOC_DEST_EXTRAS=$(ZF_STAGE_DIR)/extras/documentation/api/extras
API_DOC_TITLE_CORE=Zend Framework API Documentation
API_DOC_TITLE_EXTRAS=Zend Framework Extras API Documentation

AMF_PRODUCT_NAME=Zend AMF
AMF_PRODUCTNAME=ZendAMF
AMF_PRODUCT=$(AMF_PRODUCTNAME)-$(ZF_VERSION)
AMF_EXPORT_DIR=$(CURDIR)/exports/$(AMF_PRODUCT)
AMF_STAGE_DIR=$(STAGE_HOME)/$(AMF_PRODUCT)
AMF_RELEASE_DIR=$(RELEASES_HOME)/$(AMF_PRODUCT)
AMF_APIDOC_DEST=$(AMF_STAGE_DIR)/documentation/api/core
AMF_APIDOC_TITLE=Zend AMF API Documentation
AMF_ZIPBALL=$(AMF_PRODUCT).zip
AMF_TARBALL=$(AMF_PRODUCT).tar.gz

GDATA_PRODUCT_NAME=Zend Gdata
GDATA_PRODUCTNAME=ZendGdata
GDATA_PRODUCT=$(GDATA_PRODUCTNAME)-$(ZF_VERSION)
GDATA_EXPORT_DIR=$(CURDIR)/exports/$(GDATA_PRODUCT)
GDATA_STAGE_DIR=$(STAGE_HOME)/$(GDATA_PRODUCT)
GDATA_RELEASE_DIR=$(RELEASES_HOME)/$(GDATA_PRODUCT)
GDATA_APIDOC_DEST=$(GDATA_STAGE_DIR)/documentation/api/core
GDATA_APIDOC_TITLE=Zend Gdata API Documentation
GDATA_ZIPBALL=$(GDATA_PRODUCT).zip
GDATA_TARBALL=$(GDATA_PRODUCT).tar.gz

.PHONY: version clean clean-docbook clean-export

dist: stage archive

version:
	@if [ "$(ZF_VERSION)" = "false" ] ; then \
		echo "Please pass the ZF_VERSION assignment when calling make" ; \
		exit 42 ; \
	fi

export: version
	@echo "Exporting files from git and source archives..."
	@if [ -d "$(ZF_EXPORT_DIR)" ] ; then \
	    echo "Export directory already exists ($(ZF_EXPORT_DIR))" ; \
	else \
		mkdir -p $(ZF_EXPORT_DIR) ; \
		$(WGET) -nc $(ZF_ARCHIVE_URL)/zf1/tarball/release-$(ZF_VERSION) -O $(DOWNLOADS)/release-$(ZF_VERSION).tgz ; \
		(cd $(ZF_EXPORT_DIR) && $(TAR) xzf $(DOWNLOADS)/release-$(ZF_VERSION).tgz --strip-components=1) ; \
		$(WGET) -nc $(ZF_ARCHIVE_URL)/zf1-extras/tarball/master -O $(DOWNLOADS)/zf1-extras-release-$(ZF_VERSION).tgz ; \
		mkdir -p $(ZF_EXPORT_DIR)/extras ; \
		(cd $(ZF_EXPORT_DIR)/extras && $(TAR) xzf $(DOWNLOADS)/zf1-extras-release-$(ZF_VERSION).tgz --strip-components=1 --overwrite) ; \
		$(WGET) -nc $(DOJO_URL) -O $(DOWNLOADS)/dojo.tgz ; \
		mkdir -p $(ZF_EXPORT_DIR)/externals/dojo ; \
		(cd $(ZF_EXPORT_DIR)/externals/dojo && $(TAR) xzf $(DOWNLOADS)/dojo.tgz --strip-components=1 --overwrite) ; \
	fi
	@echo "...Done exporting files from git and source archives."

stage: stage-files stage-version stage-docs stage-extras-docs stage-minimal stage-minimal-version

stage-files: export
	@echo "Staging files..."
	@if [ -d "$(ZF_STAGE_DIR)" ] ; then \
	    echo "Staging directory already exists ($(ZF_STAGE_DIR))" ; \
	else \
	    mkdir -p $(ZF_STAGE_DIR) ; \
	    (cd $(ZF_EXPORT_DIR) && rsync --archive --delete --exclude-from="$(CONFIGS)/exclude-files-fw" ./ $(ZF_STAGE_DIR)) ; \
	    (cd $(ZF_STAGE_DIR) && $(SCRIPTS)/runZfCreateDojoSourcePackage.sh externals.tmp && rm -rf externals && mv externals.tmp/externals ./ && rmdir externals.tmp tmp artifacts) ; \
	fi
	@echo "...Done staging files."

stage-minimal: export
	@echo "[Minimal] Staging files for distribution..."
	@if [ -d "$(ZF_MINIMAL_STAGE_DIR)" ] ; then \
	    echo "Staging directory already exists ($(ZF_MINIMAL_STAGE_DIR))" ; \
	else \
		echo "... Copying subset of ZF files for minimal distribution..." ; \
		mkdir -p $(ZF_MINIMAL_EXPORT_DIR) ; \
		(cd $(ZF_EXPORT_DIR) && rsync --quiet --archive --delete --recursive \
			--files-from="$(CONFIGS)/include-files-minimal" \
			./ $(ZF_MINIMAL_EXPORT_DIR)/) ; \
		echo "... Staging minimal files..." ; \
	    mkdir -p $(ZF_MINIMAL_STAGE_DIR) ; \
	    (cd $(ZF_MINIMAL_EXPORT_DIR) && rsync --quiet --archive --delete \
			--exclude-from="$(CONFIGS)/exclude-files-minimal" ./ $(ZF_MINIMAL_STAGE_DIR)/) ; \
	fi
	@echo "...[Minimal] Done staging files."

stage-docs: docbook phpdoc

docbook: docbook-core

docbook-core: stage-files
	@echo "Building and staging DocBook documentation..."
	@if [ -d "$(ZF_STAGE_DIR)/documentation/manual/core" ] ; then \
		echo "DocBook documentation has already been staged" ; \
	else \
	    mkdir -p $(ZF_STAGE_DIR)/documentation/manual/core ; \
	    ( cd $(ZF_EXPORT_DIR) && $(PHP) $(DOC_UTILS)/mergeExtras.php '$(ZF_LANG)' ) ; \
	    for lang in $(ZF_LANG) ; do \
			echo "Building manual for language $$lang..." ; \
			$(SCRIPTS)/docbook.sh "$(MAKE)" "$(DOC_UTILS)" "$(ZF_EXPORT_DIR)/documentation/manual/$$lang" "$(ZF_STAGE_DIR)/documentation/manual/core/$$lang" ; \
	        echo "...Done building and staging DocBook documentation for language: $$lang." ; \
	    done ; \
	fi
	@echo "...Done building and staging DocBook documentation."

phpdoc: phpdoc-core

phpdoc-core: stage-files
	@echo "Building and staging PHP API documentation..."
	-mkdir -p $(API_DOC_DEST_CORE)
	-$(SCRIPTS)/phpdoc.sh "$(PHPDOC)" "$(ZF_STAGE_DIR)/library" "$(API_DOC_DEST_CORE)" "$(API_DOC_TITLE_CORE)" "$(API_DOC_OUTPUT_STYLE)"
	@echo "...Done building and staging PHP API documentation."

stage-extras-docs: extras-docbook extras-phpdoc

extras-docbook: extras-docbook-core

extras-docbook-core: stage-files
	@echo "Building and staging Extras DocBook documentation..."
	@if [ -d "$(ZF_STAGE_DIR)/extras/documentation/manual/extras" ] ; then \
		echo "Extras DocBook documentation has already been staged" ; \
	else \
		mkdir -p $(ZF_STAGE_DIR)/extras/documentation/manual/extras ; \
		for lang in $(ZF_LANG_EXTRAS) ; do \
		    src=$(ZF_EXPORT_DIR)/extras/documentation/manual/$$lang ; \
		    dest=$(ZF_STAGE_DIR)/extras/documentation/manual/extras/$$lang ; \
		    echo "Building and staging Extras DocBook documentation for language: $$lang..." ; \
		    ( cd $$src && autoconf && sh ./configure && $(MAKE) -e 2>&1 | tee err.txt ) ; \
		    rsync --archive --delete $$src/html/ $$dest ; \
		    echo "...Done building and staging Extras DocBook documentation for language: $$lang." ; \
		done ; \
	fi
	@echo "...Done building and staging Extras DocBook documentation."

extras-phpdoc: extras-phpdoc-core

extras-phpdoc-core: stage-files
	@echo "Building and staging Extras PHP API documentation..."
	-mkdir -p $(API_DOC_DEST_EXTRAS)
	-$(SCRIPTS)/phpdoc.sh "$(PHPDOC)" "$(ZF_STAGE_DIR)/extras/library" "$(API_DOC_DEST_EXTRAS)" "$(API_DOC_TITLE_EXTRAS)" "$(API_DOC_OUTPUT_STYLE)"
	@echo "...Done building and staging PHP API documentation."

stage-version: stage-files
	@echo "Adding release date to release notes..."
	-$(SCRIPTS)/stage-version.sh $(ZF_STAGE_DIR)
	@echo "...Done adding release date to release notes."

stage-minimal-version: stage-minimal
	@echo "[Minimal] Adding release date to release notes..."
	-$(SCRIPTS)/stage-version.sh $(ZF_MINIMAL_STAGE_DIR)
	@echo "...[Minimal] Done adding release date to release notes."

archive: zip tar zip-minimal tar-minimal

zip: stage
	@echo "Creating ZIP archives..."
	@if [ -f "$(STAGE_HOME)/$(ZF_ZIPBALL)" ] ; then \
		echo "Code ZIP archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(ZIP) -rq $(ZF_ZIPBALL) $(ZF_PRODUCT) -x $(ZF_PRODUCT)/documentation/\* ) ; \
		echo "Code archive done..." ; \
	fi
	@if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-apidoc.zip" ] ; then \
		echo "API Documentation ZIP archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(ZIP) -rq $(ZF_PRODUCT)-apidoc.zip \
			$(ZF_PRODUCT)/documentation/api ) ; \
		echo "Documentation archive for API doc done..." ; \
	fi
	@if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-apidoc.zip" ] ; then \
		echo "Manual documentation (en) ZIP archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(ZIP) -rq $(ZF_PRODUCT)-manual-en.zip \
			$(ZF_PRODUCT)/documentation/manual/core/en ) ; \
		echo "Documentation archive for manual language 'en' done..." ; \
	fi
	@for language in `ls -1d $(STAGE_HOME)/$(ZF_PRODUCT)/documentation/manual/core/* | grep -v '/en$$'` ; do \
	    lang=`basename $$language` ; \
		if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-manual-$$lang.zip" ] ; then \
			echo "Manual documentation ($$lang) ZIP archive already exists" ; \
		else \
			( cd $(STAGE_HOME) && $(ZIP) -rq $(ZF_PRODUCT)-manual-$$lang.zip \
				$(ZF_PRODUCT)/documentation/manual/core/$$lang ) ; \
			echo "Documentation archive for manual language '$$lang' done..." ; \
		fi ; \
	done
	@echo "...Done creating ZIP archives."

zip-minimal: stage-minimal
	@echo "[Minimal] Creating ZIP archives..."
	@(cd $(STAGE_HOME) && $(ZIP) -rq $(ZF_MINIMAL_ZIPBALL) $(ZF_MINIMAL))
	@echo "...[Minimal] Done creating ZIP archives."

tar: stage
	@echo "Creating TAR.GZ archives..."
	@if [ -f "$(STAGE_HOME)/$(ZF_TARBALL)" ] ; then \
		echo "Code TAR archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(TAR) -czf $(ZF_TARBALL) --exclude=$(ZF_PRODUCT)/documentation $(ZF_PRODUCT) ) ; \
		echo "Code archive done..." ; \
	fi
	@if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-apidoc.tar.gz" ] ; then \
		echo "API Documentation TAR archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(TAR) -czf $(ZF_PRODUCT)-apidoc.tar.gz \
			$(ZF_PRODUCT)/documentation/api ) ; \
		echo "Documentation archive for API doc done..." ; \
	fi
	@if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-apidoc.tar.gz" ] ; then \
		echo "Manual documentation (en) TAR archive already exists" ; \
	else \
		( cd $(STAGE_HOME) && $(TAR) -czf $(ZF_PRODUCT)-manual-en.tar.gz \
			$(ZF_PRODUCT)/documentation/manual/core/en ) ; \
		echo "Documentation archive for manual language 'en' done..." ; \
	fi
	@for language in `ls -1d $(STAGE_HOME)/$(ZF_PRODUCT)/documentation/manual/core/* | grep -v '/en$$'` ; do \
	    lang=`basename $$language` ; \
		if [ -f "$(STAGE_HOME)/$(ZF_PRODUCT)-manual-$$lang.tar.gz" ] ; then \
			echo "Manual documentation ($$lang) TAR archive already exists" ; \
		else \
			( cd $(STAGE_HOME) && $(TAR) -czf $(ZF_PRODUCT)-manual-$$lang.tar.gz \
				$(ZF_PRODUCT)/documentation/manual/core/$$lang ) ; \
			echo "Documentation archive for manual language '$$lang' done..." ; \
		fi ; \
	done
	@echo "...Done creating TAR.GZ archives."

tar-minimal: stage-minimal
	@echo "[Minimal] Creating TAR.GZ archives..."
	@(cd $(STAGE_HOME) && $(TAR) -czf $(ZF_MINIMAL_TARBALL) $(ZF_MINIMAL))
	@echo "...[Minimal] Done creating TAR.GZ archives." ; \

clean: version
	-rm -f $(DOWNLOADS)/*.tgz
	-rm -rf $(ZF_STAGE_DIR)
	-rm -f $(STAGE_HOME)/$(ZF_PRODUCT)*.zip
	-rm -f $(STAGE_HOME)/$(ZF_PRODUCT)*.tar.gz
	-rm -rf $(ZF_MINIMAL_STAGE_DIR)
	-rm -f $(STAGE_HOME)/$(ZF_MINIMAL).zip
	-rm -f $(STAGE_HOME)/$(ZF_MINIMAL).tar.gz
	-rm -rf $(AMF_STAGE_DIR)
	-rm -f $(STAGE_HOME)/$(AMF_PRODUCT).zip
	-rm -f $(STAGE_HOME)/$(AMF_PRODUCT).tar.gz

clean-docbook: version
	@for language in $(ZF_EXPORT_DIR)/documentation/manual/$(ZF_LANG) $(ZF_EXPORT_DIR)/incubator/documentation/manual/$(ZF_LANG) ; do \
		$(SCRIPTS)/clean-docbook.sh "$$language" ; \
	done

release: version
	@echo "Copying release files to release directory ($(ZF_RELEASE_DIR))"
	-$(SCRIPTS)/release.sh "$(ZF_PRODUCT)" "$(STAGE_HOME)" "$(ZF_RELEASE_DIR)"
	@echo "...Done copying release files to release directory" ; \

clean-export: version
	-rm -rf $(ZF_EXPORT_DIR)
	-rm -rf $(ZF_MINIMAL_EXPORT_DIR)
	-rm -rf $(AMF_EXPORT_DIR)

amf: stage-amf archive-amf

stage-amf: stage-amf-files stage-amf-docs stage-amf-version

stage-amf-files: export
	@echo "[AMF] Staging files for distribution..."
	-$(SCRIPTS)/stage-files.sh "$(AMF_STAGE_DIR)" "$(ZF_EXPORT_DIR)" "$(AMF_EXPORT_DIR)" "$(CONFIGS)/include-files-amf" "$(CONFIGS)/exclude-files-amf" "$(DOC_UTILS)/zend-amf-manual.xml" "[Aa]mf"
	@echo "...[AMF] Done staging files."

stage-amf-docs: docbook-amf phpdoc-amf

docbook-amf: stage-amf-files
	@echo "[AMF] Building and staging DocBook documentation..."
	@if [ -d "$(AMF_STAGE_DIR)/documentation/manual/core" ] ; then \
		echo "DocBook documentation has already been staged" ; \
	else \
		mkdir -p $(AMF_STAGE_DIR)/documentation/manual/core ; \
		$(SCRIPTS)/docbook.sh "$(MAKE)" "$(DOC_UTILS)" "$(AMF_EXPORT_DIR)/documentation/manual/en" "$(AMF_STAGE_DIR)/documentation/manual/core/en" ; \
	fi
	@echo "...[AMF] Done building and staging DocBook documentation."

phpdoc-amf: stage-amf-files
	@echo "[AMF] Building and staging PHP API documentation..."
	-mkdir -p $(AMF_APIDOC_DEST)
	-$(SCRIPTS)/phpdoc.sh "$(PHPDOC)" "$(AMF_STAGE_DIR)/library" "$(AMF_APIDOC_DEST)" "$(AMF_APIDOC_TITLE)" "$(API_DOC_OUTPUT_STYLE)"
	@echo "...[AMF] Done building and staging PHP API documentation."

stage-amf-version: stage-amf-files
	@echo "[AMF] Adding release date to release notes..."
	-$(SCRIPTS)/stage-version.sh $(AMF_STAGE_DIR)
	@echo "...[AMF] Done adding release date to release notes."

archive-amf: zip-amf tar-amf

zip-amf: stage-amf
	@echo "[AMF] Creating ZIP archives..."
	@(cd $(STAGE_HOME) && $(ZIP) -rq $(AMF_ZIPBALL) $(AMF_PRODUCT))
	@echo "...[AMF] Done creating ZIP archives."

tar-amf: stage-amf
	@echo "[AMF] Creating TAR.GZ archives..."
	@(cd $(STAGE_HOME) && $(TAR) -czf $(AMF_TARBALL) $(AMF_PRODUCT))
	@echo "...[AMF] Done creating TAR.GZ archives." ; \

release-amf: version
	@echo "[AMF] Copying release files to release directory ($(AMF_RELEASE_DIR))"
	-$(SCRIPTS)/release.sh "$(AMF_PRODUCT)" "$(STAGE_HOME)" "$(AMF_RELEASE_DIR)"
	@echo "...[AMF] Done copying release files to release directory" ; \

clean-docbook-amf: version
	-$(SCRIPTS)/clean-docbook.sh "$(AMF_EXPORT_DIR)/documentation/manual/en"

gdata: stage-gdata archive-gdata

stage-gdata: stage-gdata-files stage-gdata-docs stage-gdata-version

stage-gdata-files: export
	@echo "[GData] Staging files for distribution..."
	-$(SCRIPTS)/stage-files.sh "$(GDATA_STAGE_DIR)" "$(ZF_EXPORT_DIR)" "$(GDATA_EXPORT_DIR)" "$(CONFIGS)/include-files-gdata" "$(CONFIGS)/exclude-files-gdata" "$(DOC_UTILS)/zend-gdata-manual.xml" "[Gg]data"
	@echo "...[GData] Done staging files."

stage-gdata-docs: docbook-gdata phpdoc-gdata

docbook-gdata: stage-gdata-files
	@echo "[GData] Building and staging DocBook documentation..."
	@if [ -d "$(GDATA_STAGE_DIR)/documentation/manual/core" ] ; then \
		echo "DocBook documentation has already been staged" ; \
	else \
		mkdir -p $(GDATA_STAGE_DIR)/documentation/manual/core ; \
		$(SCRIPTS)/docbook.sh "$(MAKE)" "$(DOC_UTILS)" "$(GDATA_EXPORT_DIR)/documentation/manual/en" "$(GDATA_STAGE_DIR)/documentation/manual/core/en" ; \
	fi
	@echo "...[GData] Done building and staging DocBook documentation."

phpdoc-gdata: stage-gdata-files
	@echo "[GData] Building and staging PHP API documentation..."
	-mkdir -p $(GDATA_APIDOC_DEST)
	-$(SCRIPTS)/phpdoc.sh "$(PHPDOC)" "$(GDATA_STAGE_DIR)/library" "$(GDATA_APIDOC_DEST)" "$(GDATA_APIDOC_TITLE)" "$(API_DOC_OUTPUT_STYLE)"
	@echo "...[GData] Done building and staging PHP API documentation."

stage-gdata-version: stage-gdata-files
	@echo "[GData] Adding release date to release notes..."
	-$(SCRIPTS)/stage-version.sh $(GDATA_STAGE_DIR)
	@echo "...[GData] Done adding release date to release notes."

archive-gdata: zip-gdata tar-gdata

zip-gdata: stage-gdata
	@echo "[GData] Creating ZIP archives..."
	@(cd $(STAGE_HOME) && $(ZIP) -rq $(GDATA_ZIPBALL) $(GDATA_PRODUCT))
	@echo "...[GData] Done creating ZIP archives."

tar-gdata: stage-gdata
	@echo "[GData] Creating TAR.GZ archives..."
	@(cd $(STAGE_HOME) && $(TAR) -czf $(GDATA_TARBALL) $(GDATA_PRODUCT))
	@echo "...[GData] Done creating TAR.GZ archives." ; \

release-gdata: version
	@echo "[GData] Copying release files to release directory ($(GDATA_RELEASE_DIR))"
	-$(SCRIPTS)/release.sh "$(GDATA_PRODUCT)" "$(STAGE_HOME)" "$(GDATA_RELEASE_DIR)"
	@echo "...[GData] Done copying release files to release directory" ; \

clean-docbook-gdata: version
	-$(SCRIPTS)/clean-docbook.sh "$(GDATA_EXPORT_DIR)/documentation/manual/en"


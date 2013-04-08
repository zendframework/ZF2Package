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
STAGE_HOME=$(CURDIR)/stage

ZF_ARCHIVE_URL=https://api.github.com/repos/zendframework
ZF_PRODUCT_NAME=Zend Framework
ZF_PRODUCTNAME=ZendFramework
ZF_PRODUCT=$(ZF_PRODUCTNAME)-$(ZF_VERSION)

ZF_EXPORT_DIR=$(CURDIR)/exports/$(ZF_PRODUCT)
ZF_STAGE_DIR=$(STAGE_HOME)/$(ZF_PRODUCT)
ZF_RELEASE_DIR=$(CURDIR)/../../public/releases/$(ZF_PRODUCTNAME)-$(ZF_VERSION)

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

.PHONY: version clean clean-docbook clean-export

dist: export stage archive

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
	        src=$(ZF_EXPORT_DIR)/documentation/manual/$$lang ; \
	        dest=$(ZF_STAGE_DIR)/documentation/manual/core/$$lang ; \
	        echo "Converting manual to DocBook 5 for language: $$lang..." ; \
	        ( cd $$src && autoconf && sh ./configure && $(MAKE) manual.xml 2>&1 | tee err.txt) ; \
	        ( $(DOC_UTILS)/fix-docbook.sh $$src/manual.xml ) ; \
	        ( xsltproc --xinclude $(DOC_UTILS)/db4-upgrade.xsl $$src/manual.xml > $$src/manual.db5.xml | tee -a $$src/err.txt) ; \
	        echo "Building and staging DocBook documentation for language: $$lang..." ; \
	        ( $(DOC_UTILS)/pear/phd -g 'phpdotnet\phd\Highlighter_GeSHi' --xinclude -f zfpackage -d $$src/manual.db5.xml -o $$src/output ) ; \
	        rsync --archive --delete $$src/output/zf-package-chunked-xhtml/ $$dest ; \
	        echo "...Done building and staging DocBook documentation for language: $$lang." ; \
	    done ; \
	fi
	@echo "...Done building and staging DocBook documentation."

phpdoc: phpdoc-core

phpdoc-core: stage-files
	@echo "Building and staging PHP API documentation..."
	-mkdir -p $(API_DOC_DEST_CORE)
	@if [ ! -f $(API_DOC_DEST_CORE)/index.html -o `find $(ZF_STAGE_DIR)/library -newer $(API_DOC_DEST_CORE)/index.html 2>/dev/null | wc -l` -gt 0 ] ; then \
		$(PHPDOC) project:run \
		  --verbose \
	      --target $(API_DOC_DEST_CORE) \
	      --directory $(ZF_STAGE_DIR)/library \
	      --title "$(API_DOC_TITLE_CORE)" \
	      --template $(API_DOC_OUTPUT_STYLE) ; \
	else \
	    echo "PHP API documentation is already staged and up to date." ; \
	fi
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
	@if [ ! -f $(API_DOC_DEST_EXTRAS)/index.html -o `find $(ZF_STAGE_DIR)/extras/library -newer $(API_DOC_DEST_EXTRAS)/index.html 2>/dev/null | wc -l` -gt 0 ] ; then \
		$(PHPDOC) project:run \
		  --verbose \
	      --target $(API_DOC_DEST_EXTRAS) \
	      --directory $(ZF_STAGE_DIR)/extras/library \
	      --title "$(API_DOC_TITLE_EXTRAS)" \
	      --template $(API_DOC_OUTPUT_STYLE) ; \
	else \
	    echo "PHP API documentation is already staged and up to date." ; \
	fi
	@echo "...Done building and staging PHP API documentation."

stage-version: stage-files
	@echo "Adding release date to release notes..."
	-sed -e 's/ (\[INSERT REV NUM HERE\])//' \
	    < $(ZF_STAGE_DIR)/README.md \
	    > $(ZF_STAGE_DIR)/README.md.new
	@ZF_RELEASE_DATE=`date +%Y-%m-%d` ; sed -e "s/<Month> <Day>, <Year>/$$ZF_RELEASE_DATE/" \
	    < $(ZF_STAGE_DIR)/README.md.new \
	    > $(ZF_STAGE_DIR)/README.md
	-rm $(ZF_STAGE_DIR)/README.md.new
	@echo "...Done adding release date to release notes."

stage-minimal-version: stage-minimal
	@echo "[Minimal] Adding release date to release notes..."
	-sed -e 's/ (\[INSERT REV NUM HERE\])//' \
	    < $(ZF_MINIMAL_STAGE_DIR)/README.md \
	    > $(ZF_MINIMAL_STAGE_DIR)/README.md.new
	@ZF_RELEASE_DATE=`date +%Y-%m-%d` ; sed -e "s/<Month> <Day>, <Year>/$$ZF_RELEASE_DATE/" \
	    < $(ZF_MINIMAL_STAGE_DIR)/README.md.new \
	    > $(ZF_MINIMAL_STAGE_DIR)/README.md
	-rm $(ZF_MINIMAL_STAGE_DIR)/README.md.new
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
	@echo "[Minimal] Code archive done..." ; \

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
	@echo "[Minimal] Code archive done..." ; \

clean: version
	-rm -f $(DOWNLOADS)/*.tgz
	-rm -rf $(ZF_STAGE_DIR)
	-rm -f $(STAGE_HOME)/$(ZF_PRODUCT)*.zip
	-rm -f $(STAGE_HOME)/$(ZF_PRODUCT)*.tar.gz
	-rm -rf $(ZF_MINIMAL_STAGE_DIR)
	-rm -f $(STAGE_HOME)/$(ZF_MINIMAL)*.zip
	-rm -f $(STAGE_HOME)/$(ZF_MINIMAL)*.tar.gz

clean-docbook: version
	@for language in $(ZF_EXPORT_DIR)/documentation/manual/$(ZF_LANG) $(ZF_EXPORT_DIR)/incubator/documentation/manual/$(ZF_LANG) ; do \
	    rm -rf $$language/autom4te.cache $$language/build/docbook-xsl $$language/html/*.html $$language/html/HTML.manifest $$language/config.* $$language/configure $$language/entities.ent $$language/Makefile ; \
	done

release: dist
	@echo "Copying release files to release directory ($(ZF_RELEASE_DIR))"
	@if [ -d "$(ZF_RELEASE_DIR)" ] ; then \
		echo "Release directory already exists ($(ZF_RELEASE_DIR))" ; \
	else \
		mkdir -p $(ZF_RELEASE_DIR) ; \
		cp $(STAGE_HOME)/$(ZF_PRODUCTNAME)-$(ZF_VERSION)*.zip $(ZF_RELEASE_DIR) ; \
		cp $(STAGE_HOME)/$(ZF_PRODUCTNAME)-$(ZF_VERSION)*.tar.gz $(ZF_RELEASE_DIR) ; \
	fi
	@echo "...Done copying release files to release directory" ; \

clean-export: version
	-rm -rf $(ZF_EXPORT_DIR)
	-rm -rf $(ZF_MINIMAL_EXPORT_DIR)

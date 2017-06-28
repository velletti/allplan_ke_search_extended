Echo "you need to setup the Directory !!! "
Echo "Set up a new file Watcher with following settings"
echo "program: point to this file f.e.  C:\web\allplan\Allplan\scripts\dev\copy_ke_search_extended.bat"

echo "arguments: relative path to the TYPO3 instance typo3conf/ext path f.e. ../allplan/Allplan/http/typo3conf/ext/allplan_ke_search_extended"
echo "Other Options -> the working directory : root folder of repository (f.e. ( C:\web\repos\ )"
echo "Got this as argument: %1 "

robocopy ".\allplan_ke_search_extended" "%1" /E /XD .git /XD .idea



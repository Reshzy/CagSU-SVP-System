@echo off
REM Package CapstoneLatest for Teacher Submission
REM This script creates a clean zip file ready for distribution

echo ================================================
echo Packaging CapstoneLatest for Teacher Submission
echo ================================================
echo.

REM Change to parent directory
cd ..

REM Clean up previous package
if exist "CapstoneLatest_Package.zip" (
    echo Deleting old package...
    del "CapstoneLatest_Package.zip"
)

REM Create temporary directory
echo Creating temporary directory...
if exist "CapstoneLatest_Clean" rmdir /s /q "CapstoneLatest_Clean"
mkdir "CapstoneLatest_Clean"

REM Copy files to clean directory
echo Copying files...
xcopy "CapstoneLatest\*" "CapstoneLatest_Clean\" /E /I /H /Y

REM Remove unnecessary files
echo Cleaning up unnecessary files...
cd "CapstoneLatest_Clean"

REM Remove .git folder
if exist ".git" (
    echo Removing .git folder...
    rmdir /s /q ".git"
)

REM Remove IDE folders
if exist ".vscode" rmdir /s /q ".vscode"
if exist ".idea" rmdir /s /q ".idea"
if exist ".fleet" rmdir /s /q ".fleet"
if exist ".nova" rmdir /s /q ".nova"
if exist ".zed" rmdir /s /q ".zed"

REM Remove log files (keep the folder structure)
if exist "storage\logs" (
    echo Clearing log files...
    del /q "storage\logs\*.log"
)

REM Clear cache and session files
if exist "storage\framework\cache" (
    del /q "storage\framework\cache\*.*"
)
if exist "storage\framework\sessions" (
    del /q "storage\framework\sessions\*.*"
)
if exist "storage\framework\views" (
    del /q "storage\framework\views\*.*"
)

REM Remove backup files
if exist ".env.backup" del /q ".env.backup"
if exist ".env.production" del /q ".env.production"

REM Go back to parent directory
cd ..

REM Create zip file
echo Creating zip file...
powershell -command "Compress-Archive -Path 'CapstoneLatest_Clean\*' -DestinationPath 'CapstoneLatest_Package.zip' -Force"

REM Clean up temporary directory
echo Cleaning up...
rmdir /s /q "CapstoneLatest_Clean"

echo.
echo ================================================
echo Package Created Successfully!
echo ================================================
echo.
echo File: CapstoneLatest_Package.zip
echo Location: %cd%
echo.
echo This zip file is ready to send to your teacher.
echo.
echo Next Steps:
echo 1. Open C:\xampp\htdocs\CapstoneLatest_Package.zip
echo 2. Verify all files are included
echo 3. Send via email or Google Drive
echo 4. Include the INSTALLATION_INSTRUCTIONS.md in your email
echo.
pause


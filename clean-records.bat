@echo off
REM Send GET request and save response to a temporary file
curl -X GET http://127.0.0.1:8000/api/cleanLogs -o response.txt

REM Display the response from the file
type response.txt

REM Add a line break
echo.

REM Delete the temporary file
del response.txt

REM Pause to keep the window open
pause
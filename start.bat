@ECHO OFF

@REM @REM for backend
@cd backend
@set PATH=php;%PATH%
start php artisan serve

@cd ..

@REM @REM for frontend   
@cd frontend
@set PATH=node;%PATH%
start npm run prod

@cd ..
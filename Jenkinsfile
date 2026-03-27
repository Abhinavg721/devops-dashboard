pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        SOCKET = "/var/run/podman.sock"
        API = "http://localhost/v4.0.0/libpod"
    }

    stages {

        stage('Deploy') {
            steps {
                sh '''
                echo "🔁 Restart Laravel..."
                curl --unix-socket $SOCKET -X POST $API/containers/laravel_app/restart

                echo "🔁 Restart Nginx..."
                curl --unix-socket $SOCKET -X POST $API/containers/nginx_proxy/restart

                echo "🔁 Restart HAProxy..."
                curl --unix-socket $SOCKET -X POST $API/containers/haproxy-lab/restart
                '''
            }
        }

        stage('Verify') {
            steps {
                sh 'curl -I http://localhost:8086 || true'
            }
        }
    }
}

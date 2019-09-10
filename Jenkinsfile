pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                script {
                    if (env.BRANCH_NAME == "stagev13") {
                        sh 'ssh -o StrictHostKeyChecking=no -tt -i /home/ubuntu/keys/staging.pem ubuntu@ec2-35-154-147-1.ap-south-1.compute.amazonaws.com "cd /var/www/html/fitternityapi; git pull; exit;"'
                    }
                    else {
                        sh 'ssh -o StrictHostKeyChecking=no -tt -i /home/ubuntu/keys/new_v2api.pem ubuntu@ec2-18-138-238-107.ap-southeast-1.compute.amazonaws.com "cd /var/www/html/fitternityapi; git pull; exit;"'
                        sh 'ssh -o StrictHostKeyChecking=no -tt -i /home/ubuntu/keys/new_v2api.pem ubuntu@ec2-18-138-241-182.ap-southeast-1.compute.amazonaws.com "cd /var/www/html/fitternityapi; git pull; exit;"'
                        sh 'ssh -o StrictHostKeyChecking=no -tt -i /home/ubuntu/keys/new_v2api.pem ubuntu@ec2-13-229-147-113.ap-southeast-1.compute.amazonaws.com "cd /var/www/html/fitternityapi; git pull; exit;"'
                    }
                }
            }
        } 
    }
}
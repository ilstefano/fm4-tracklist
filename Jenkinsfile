pipeline {
  agent any
  stages {
    stage('Build') {
      steps {
        sh 'echo "Build"'
        pwd(tmp: true)
      }
    }
    stage('Test') {
      steps {
        sh 'echo "Test"'
      }
    }
    stage('Deploy') {
      steps {
        sh 'echo "Deploy"'
      }
    }
  }
}
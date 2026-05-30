# Infrastructure Engineering Spec — Kubernetes + Cloud

## Deployment Architecture
```
┌──────────────────────────────────────────────────────────────┐
│                     Production Cluster                       │
│                      Kubernetes (EKS/AKS)                    │
├──────────────────────────────────────────────────────────────┤
│                                                               
│  Namespace: beza-platform                                     
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  API Gateway (Kong)  │  │  Web App (React)    │            │
│  │  Replicas: 2-5       │  │  Replicas: 2-3      │            │
│  │  CPU: 1, RAM: 2GB   │  │  CPU: 1, RAM: 2GB   │            │
│  └─────────────────────┘  └─────────────────────┘            │
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  Laravel API         │  │  Laravel Queue      │            │
│  │  Replicas: 3-10      │  │  Replicas: 2-5      │            │
│  │  CPU: 2, RAM: 4GB   │  │  CPU: 2, RAM: 4GB   │            │
│  └─────────────────────┘  └─────────────────────┘            │
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  MySQL (Primary)     │  │  MySQL (Read Replica)│           │
│  │  CPU: 4, RAM: 16GB  │  │  CPU: 4, RAM: 16GB  │           │
│  │  Storage: 200GB SSD │  │  Storage: 200GB SSD │           │
│  └─────────────────────┘  └─────────────────────┘            │
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  Redis (Cache)       │  │  Redis (Session)    │            │
│  │  CPU: 2, RAM: 8GB   │  │  CPU: 2, RAM: 4GB   │            │
│  └─────────────────────┘  └─────────────────────┘            │
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  RabbitMQ            │  │  Elasticsearch      │            │
│  │  Replicas: 3         │  │  Replicas: 3        │            │
│  │  CPU: 2, RAM: 4GB   │  │  CPU: 4, RAM: 8GB   │            │
│  └─────────────────────┘  └─────────────────────┘            │
│                                                               
│  ┌─────────────────────┐  ┌─────────────────────┐            │
│  │  ClickHouse          │  │  Prometheus +       │            │
│  │  CPU: 4, RAM: 16GB  │  │  Grafana            │            │
│  └─────────────────────┘  └─────────────────────┘            │
└──────────────────────────────────────────────────────────────┘
```

## CI/CD Pipeline (GitLab CI)
```yaml
stages:
  - test
  - build
  - deploy-staging
  - test-staging
  - deploy-production
  - smoke-test

variables:
  DOCKER_REGISTRY: registry.beza.com
  KUBE_NAMESPACE: beza-platform

php-tests:
  stage: test
  image: php:8.3-cli
  script:
    - composer install
    - php artisan test --parallel --coverage --min=80

build-api:
  stage: build
  script:
    - docker build -t $DOCKER_REGISTRY/api:$CI_COMMIT_SHA .
    - docker push $DOCKER_REGISTRY/api:$CI_COMMIT_SHA
  only:
    - main
    - staging

deploy-staging:
  stage: deploy-staging
  script:
    - kubectl set image deployment/api api=$DOCKER_REGISTRY/api:$CI_COMMIT_SHA
    - kubectl rollout status deployment/api
  environment: staging
  only:
    - staging

deploy-production:
  stage: deploy-production
  script:
    - kubectl set image deployment/api api=$DOCKER_REGISTRY/api:$CI_COMMIT_SHA
    - kubectl rollout status deployment/api --timeout=5m
  environment: production
  when: manual
  only:
    - main
```

## Infrastructure as Code (Terraform)
```hcl
# Main infrastructure module
module "kubernetes_cluster" {
  source = "./modules/eks"
  
  cluster_name    = "beza-production"
  node_groups = {
    api = {
      instance_type = "t3.large"
      min_size     = 3
      max_size     = 10
      desired_size = 3
    }
    data = {
      instance_type = "r5.xlarge"
      min_size     = 2
      max_size     = 5
      desired_size = 2
    }
  }
}

module "mysql" {
  source = "./modules/rds"
  
  instance_class = "db.r5.large"
  storage_gb     = 200
  multi_az       = true
  backup_retention_days = 30
}

module "redis" {
  source = "./modules/elasticache"
  
  node_type = "cache.r5.large"
  num_nodes = 2
  multi_az  = true
}
```

## Disaster Recovery
```
Recovery Point Objective (RPO): 5 minutes
Recovery Time Objective (RTO): 1 hour

Backup Strategy:
  - MySQL: WAL archiving (continuous), full backup (daily)
  - Redis: RDB snapshots (hourly)
  - RabbitMQ: Queue definitions (infrequent), messages ephemeral
  - Elasticsearch: Snapshot to S3 (daily)
  - ClickHouse: Backup to S3 (daily)

DR Runbook:
  1. Detect primary region failure (health check every 30s)
  2. Route DNS to secondary region (Route53 failover)
  3. Promote MySQL read replica in DR region to primary
  4. Restore Redis from latest RDB
  5. Scale up API pods in DR region
  6. Verify all services healthy
  7. Notify users of failover event
```

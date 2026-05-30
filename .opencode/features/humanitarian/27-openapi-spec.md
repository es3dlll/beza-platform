# OpenAPI 3.0 Specification (Excerpt)

Below is the OpenAPI 3.0 specification for the Humanitarian Aid module. Full spec file: `specs/humanitarian-openapi.yaml`

```yaml
openapi: "3.0.3"
info:
  title: Beza Humanitarian Aid API
  description: |
    API for managing humanitarian cash and voucher assistance programs.
    Supports MPC, CCT, and e-voucher distributions in Syria.
  version: "1.0.0"
  contact:
    name: Beza Platform
    url: https://beza.iq
servers:
  - url: https://api.beza.iq/v1/humanitarian
    description: Production server (Bahrain)
  - url: https://api-staging.beza.iq/v1/humanitarian
    description: Staging server

components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key

  schemas:
    AidProgram:
      type: object
      required: [ngo_id, name_ar, program_type, currency, budget, start_date, end_date]
      properties:
        ngo_id:
          type: string
          example: ngo_sarc_001
        name_ar:
          type: string
          example: برنامج المساعدة النقدية متعددة الأغراض
        name_en:
          type: string
          example: Multi-Purpose Cash Assistance Program
        program_type:
          type: string
          enum: [mpc, cct, voucher, mixed]
        currency:
          type: string
          example: USD
        budget:
          type: number
          format: double
          example: 5000000.00
        distribution_rules:
          type: object
          properties:
            amount_per_household:
              type: number
              example: 75.00
            frequency:
              type: string
              enum: [one_time, weekly, monthly, quarterly]
            max_distributions:
              type: integer
              example: 7

    BeneficiaryUploadResponse:
      type: object
      properties:
        batch_id:
          type: string
        total_records:
          type: integer
        valid:
          type: integer
        errors:
          type: integer
        error_details:
          type: array
          items:
            type: object
            properties:
              row:
                type: integer
              field:
                type: string
              message:
                type: string

    DistributionRequest:
      type: object
      required: [program_id, distribution_type]
      properties:
        program_id:
          type: string
        distribution_type:
          type: string
          enum: [mpc, cct]
        amount_per_beneficiary:
          type: number
          example: 75.00
        schedule:
          type: string
          enum: [immediate, scheduled]
          default: immediate

    SpendingReport:
      type: object
      properties:
        total_disbursed:
          type: number
        total_spent:
          type: number
        burn_rate:
          type: object
          properties:
            "7_days":
              type: number
            "14_days":
              type: number
            "30_days":
              type: number
        by_category:
          type: object
          additionalProperties:
            type: object
            properties:
              amount:
                type: number
              percentage:
                type: number

    DonorReport:
      type: object
      properties:
        report_id:
          type: string
        generated_at:
          type: string
          format: date-time
        ngo:
          type: object
        summary:
          type: object
        programs:
          type: array
        spending_analysis:
          type: object
        reconciliation:
          type: object

paths:
  /programs:
    post:
      summary: Create a new aid program
      tags: [Programs]
      security: [{BearerAuth: [humanitarian:write]}]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AidProgram'
      responses:
        '201':
          description: Program created
        '400':
          description: Validation error
        '422':
          description: Sanctions or compliance issue

  /programs/{id}/beneficiaries:
    post:
      summary: Upload beneficiary CSV
      tags: [Beneficiaries]
      security: [{BearerAuth: [humanitarian:write]}]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
                file:
                  type: string
                  format: binary
                is_test:
                  type: boolean
      responses:
        '202':
          description: Batch accepted for processing
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BeneficiaryUploadResponse'

  /distribute:
    post:
      summary: Execute distribution batch
      tags: [Distributions]
      security: [{BearerAuth: [humanitarian:write]}]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/DistributionRequest'
      responses:
        '202':
          description: Distribution queued

  /distributions:
    get:
      summary: List distributions
      tags: [Distributions]
      security: [{BearerAuth: [humanitarian:read]}]
      parameters:
        - name: program_id
          in: query
          schema:
            type: string
        - name: status
          in: query
          schema:
            type: string
            enum: [processing, completed, failed, partial]
        - name: from
          in: query
          schema:
            type: string
            format: date
        - name: to
          in: query
          schema:
            type: string
            format: date
        - name: page
          in: query
          schema:
            type: integer
        - name: per_page
          in: query
          schema:
            type: integer
      responses:
        '200':
          description: Distribution list

  /programs/{id}/spending:
    get:
      summary: Get MPC spending data
      tags: [Spending]
      security: [{BearerAuth: [humanitarian:read]}]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
        - name: from
          in: query
          schema:
            type: string
            format: date
        - name: to
          in: query
          schema:
            type: string
            format: date
        - name: governorate
          in: query
          schema:
            type: string
        - name: group_by
          in: query
          schema:
            type: string
            enum: [category, governorate, household_size]
      responses:
        '200':
          description: Spending report
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SpendingReport'

  /vouchers/create:
    post:
      summary: Issue e-vouchers
      tags: [Vouchers]
      security: [{BearerAuth: [humanitarian:write]}]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                program_id:
                  type: string
                item_list:
                  type: array
                  items:
                    type: object
                voucher_value:
                  type: number
                expiry_days:
                  type: integer
      responses:
        '201':
          description: Vouchers issued

  /vouchers/redeem:
    post:
      summary: Redeem a voucher
      tags: [Vouchers]
      security: [{BearerAuth: [merchant]}]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                voucher_code:
                  type: string
                pin:
                  type: string
                merchant_id:
                  type: string
                items:
                  type: array
      responses:
        '200':
          description: Voucher redeemed

  /reports/donor:
    get:
      summary: Generate donor report
      tags: [Reports]
      security: [{BearerAuth: [donor, humanitarian:read]}]
      parameters:
        - name: ngo_id
          in: query
          schema:
            type: string
        - name: program_id
          in: query
          schema:
            type: string
        - name: from
          in: query
          required: true
          schema:
            type: string
            format: date
        - name: to
          in: query
          required: true
          schema:
            type: string
            format: date
        - name: format
          in: query
          schema:
            type: string
            enum: [json, pdf, csv]
      responses:
        '200':
          description: Donor report
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/DonorReport'
```

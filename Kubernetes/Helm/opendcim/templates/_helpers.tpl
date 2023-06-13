{{/*
Expand the name of the chart.
*/}}
{{- define "opendcim.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
We truncate at 63 chars because some Kubernetes name fields are limited to this (by the DNS naming spec).
If release name contains chart name it will be used as a full name.
*/}}
{{- define "opendcim.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "opendcim.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "opendcim.labels" -}}
helm.sh/chart: {{ include "opendcim.chart" . }}
{{ include "opendcim.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "opendcim.selectorLabels" -}}
app.kubernetes.io/name: {{ include "opendcim.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "opendcim.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "opendcim.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}


{{/*
Create the env secrets range
*/}}

{{- define "helpers.list-env-secret-variables"}}
{{- range $key, $val := .Values.env.secret }}
  - name: {{ $key }}
    valueFrom:
      secretKeyRef:
        name:  db-secret
        key: {{ $key }}
{{- end }}
{{- end }}


{{- define "helpers.list-env-normal-variables"}}
{{- range $key, $val := .Values.env.normal }}
  - name: {{ $key }}
    value: {{ $val | quote }}
{{- end }}
{{- end }}
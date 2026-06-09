{{/*
Expand the name of the chart.
*/}}
{{- define "novacms.name" -}}
{{- .Chart.Name | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "novacms.fullname" -}}
{{- printf "%s-%s" .Release.Name .Chart.Name | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "novacms.labels" -}}
helm.sh/chart: {{ .Chart.Name }}-{{ .Chart.Version | replace "+" "_" }}
{{ include "novacms.selectorLabels" . }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "novacms.selectorLabels" -}}
app.kubernetes.io/name: {{ include "novacms.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
PostgreSQL host — matches bitnami/postgresql service name format
*/}}
{{- define "novacms.postgresqlHost" -}}
{{- printf "%s-postgresql" .Release.Name }}
{{- end }}

{{/*
Redis host — matches bitnami/redis master service name format
*/}}
{{- define "novacms.redisHost" -}}
{{- printf "%s-redis-master" .Release.Name }}
{{- end }}

{{/*
Ollama internal service host
*/}}
{{- define "novacms.ollamaHost" -}}
{{- printf "%s-novacms-ollama" .Release.Name }}
{{- end }}

{{/*
Reverb internal service host
*/}}
{{- define "novacms.reverbHost" -}}
{{- printf "%s-novacms-reverb" .Release.Name }}
{{- end }}

from rest_framework import serializers
from .models import Poll, Choice

class ChoiceSerializer(serializers.ModelSerializer):
    """Serializer for the Choice model."""
    class Meta:
        model = Choice
        fields = ['id', 'text', 'votes', 'narrative_path', 'created_at']
        read_only_fields = ['votes', 'created_at']

class PollSerializer(serializers.ModelSerializer):
    """Serializer for the Poll model."""
    choices = ChoiceSerializer(many=True, read_only=True)
    
    class Meta:
        model = Poll
        fields = ['id', 'question', 'description', 'created_at', 'updated_at', 
                 'is_active', 'start_time', 'end_time', 'choices']
        read_only_fields = ['created_at', 'updated_at']
    
    def to_representation(self, instance):
        representation = super().to_representation(instance)
        representation['is_open'] = instance.is_open()
        if not self.context.get('include_choices', True):
            representation.pop('choices', None)
        return representation

class PollResultsSerializer(serializers.ModelSerializer):
    """Serializer for poll results."""
    results = serializers.SerializerMethodField()
    
    class Meta:
        model = Poll
        fields = ['id', 'question', 'results']
    
    def get_results(self, obj):
        return obj.get_results()

class VoteSerializer(serializers.Serializer):
    """Serializer for voting on a choice."""
    choice_id = serializers.IntegerField()
    
    def validate_choice_id(self, value):
        try:
            poll_id = self.context.get('poll_id')
            choice = Choice.objects.get(id=value, poll_id=poll_id)
            return value
        except Choice.DoesNotExist:
            raise serializers.ValidationError("Invalid choice for this poll.") 